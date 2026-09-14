<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

class StripeService
{
    private StripeClient $stripe;
    private ?string $webhookSecret;

    public function __construct()
    {
        $secretKey = (string) config('services.stripe.secret', '');
        $this->webhookSecret = (string) config('services.stripe.webhook_secret', '');

        Stripe::setApiKey($secretKey);
        $this->stripe = new StripeClient($secretKey);
    }

    /**
     * Create a checkout session from an Order
     */
    public function createCheckoutSessionFromOrder(\App\Models\Order $order): array
    {
        try {
            $lineItems = [];

            if ($order->discount_amount > 0) {
                // If there is a discount, we send the total as a single line item to ensure accuracy
                // and avoid rounding discrepancies with multiple line items and discounts.
                $lineItems[] = [
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => "Pedido " . $order->order_number,
                            'description' => "Compra en Academicus (Descuento de " . number_format($order->discount_amount, 2, ',', '.') . "€ aplicado)",
                        ],
                        'unit_amount' => $this->formatAmount($order->total_amount),
                    ],
                    'quantity' => 1,
                ];
            } else {
                foreach ($order->items as $item) {
                    $lineItems[] = [
                        'price_data' => [
                            'currency' => 'eur',
                            'product_data' => [
                                'name' => $item->name,
                                'description' => "Curso Academicus - " . $item->name,
                            ],
                            'unit_amount' => $this->formatAmount($item->price),
                        ],
                        'quantity' => 1,
                    ];
                }
            }

            $session = $this->stripe->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'customer_email' => $order->email,
                'success_url' => route('shop.payment.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('shop.payment.failure'),
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ],
            ]);

            $order->update(['stripe_session_id' => $session->id]);

            return [
                'success' => true,
                'url' => $session->url,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe checkout session creation failed', [
                'error' => $e->getMessage(),
                'order_id' => $order->id,
            ]);

            return [
                'success' => false,
                'message' => 'Error al conectar con Stripe: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Create a subscription with Stripe
     */
    public function createSubscription(array $params): array
    {
        try {
            // Create customer if not exists
            $customer = $this->stripe->customers->create([
                'email' => $params['email'],
                'name' => $params['name'] ?? '',
                'metadata' => [
                    'user_id' => $params['user_id'],
                ],
            ]);

            $subscription = $this->stripe->subscriptions->create([
                'customer' => $customer->id,
                'items' => [
                    ['price' => $params['price_id']],
                ],
                'payment_behavior' => 'default_incomplete',
                'metadata' => [
                    'user_id' => $params['user_id'],
                    'plan_id' => $params['plan_id'],
                ],
            ]);

            return [
                'success' => true,
                'subscription_id' => $subscription->id,
                'customer_id' => $customer->id,
                'status' => $subscription->status,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe subscription creation failed', [
                'error' => $e->getMessage(),
                'params' => $params,
            ]);

            return [
                'success' => false,
                'message' => 'Error al crear la suscripción: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Process recurring payment for existing subscription
     */
    public function processRecurringPayment(array $params): array
    {
        try {
            $subscription = $this->stripe->subscriptions->retrieve($params['subscription_id']);

            if ($subscription->status === 'active') {
                // Create invoice for next period
                $invoice = $this->stripe->invoices->create([
                    'customer' => $subscription->customer,
                    'subscription' => $subscription->id,
                    'auto_advance' => true,
                ]);

                $invoice = $this->stripe->invoices->finalizeInvoice($invoice->id);

                if ($invoice->paid) {
                    return [
                        'success' => true,
                        'payment_id' => $invoice->payment_intent,
                        'amount' => $this->formatFromCents($invoice->amount_paid),
                    ];
                }

                // Attempt to pay the invoice
                $invoice = $this->stripe->invoices->pay($invoice->id);

                return [
                    'success' => $invoice->paid,
                    'payment_id' => $invoice->payment_intent,
                    'amount' => $this->formatFromCents($invoice->amount_paid),
                ];
            }

            return [
                'success' => false,
                'message' => 'La suscripción no está activa',
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe recurring payment failed', [
                'error' => $e->getMessage(),
                'params' => $params,
            ]);

            return [
                'success' => false,
                'message' => 'Error en el cobro recurrente: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Cancel a subscription
     */
    public function cancelSubscription(string $subscriptionId): array
    {
        try {
            $subscription = $this->stripe->subscriptions->cancel($subscriptionId);

            return [
                'success' => true,
                'status' => $subscription->status,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe subscription cancellation failed', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscriptionId,
            ]);

            return [
                'success' => false,
                'message' => 'Error al cancelar la suscripción: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Retrieve checkout session
     */
    public function getSession(string $sessionId): ?array
    {
        try {
            $session = $this->stripe->checkout->sessions->retrieve($sessionId);
            return $session->toArray();
        } catch (ApiErrorException $e) {
            Log::error('Stripe session retrieval failed', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
            ]);
            return null;
        }
    }

    /**
     * Validate webhook signature
     */
    public function validateWebhook(string $payload, string $signature): ?array
    {
        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $signature,
                $this->webhookSecret
            );

            return [
                'success' => true,
                'event' => $event,
            ];
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::error('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Invalid signature',
            ];
        }
    }

    /**
     * Format amount to cents for Stripe
     */
    private function formatAmount(float $amount): int
    {
        return (int) round($amount * 100);
    }

    /**
     * Format amount from cents to euros
     */
    private function formatFromCents(int $cents): float
    {
        return round($cents / 100, 2);
    }
}
