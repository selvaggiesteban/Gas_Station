<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\HoldedService;
use App\Services\InvoiceGeneratorService;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    private StripeService $stripe;
    private HoldedService $holded;

    public function __construct(StripeService $stripe, HoldedService $holded)
    {
        $this->stripe = $stripe;
        $this->holded = $holded;
    }

    /**
     * Show payment form and redirect to Stripe Checkout
     */
    public function show(Plan $plan)
    {
        $user = Auth::user();

        // Check if user already has an active subscription
        $activeSubscription = $user->subscription;
        if ($activeSubscription && $activeSubscription->isActive()) {
            return redirect()->route('subscription.index')
                ->with('error', 'Ya tienes una suscripción activa.');
        }

        // Create subscription or payment with Stripe
        $result = $this->stripe->createSubscription([
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'plan_id' => $plan->id,
            'price_id' => $plan->stripe_price_id, // Assuming this exists in Plan model
        ]);

        if (!$result['success']) {
            return redirect()->route('subscription.index')
                ->with('error', 'No se pudo iniciar el proceso de pago con Stripe: ' . ($result['message'] ?? 'Error desconocido'));
        }

        // For Stripe, we usually handle the actual payment via Checkout or Elements.
        // If createSubscription returns a session/url, we redirect.
        if (isset($result['url'])) {
            return redirect()->away($result['url']);
        }

        // If it's a subscription setup, we might need to show a card form or handle it via frontend.
        return view('payments.stripe', [
            'plan' => $plan,
            'subscription_id' => $result['subscription_id'],
            'client_secret' => $result['client_secret'] ?? null,
            'stripe_public_key' => config('services.stripe.public_key'),
        ]);
    }

    /**
     * Handle Stripe Webhook (Handled by StripeWebhookController, 
     * but keeping this stub if needed for future routing)
     */
    public function webhook(Request $request)
    {
        return app(StripeWebhookController::class)->handle($request);
    }

    /**
     * Handle successful payment
     */
    public function success(Request $request)
    {
        return redirect()->route('subscription.index')
            ->with('success', '¡Pago completado con éxito! Tu suscripción se está activando.');
    }

    /**
     * Handle failed payment
     */
    public function failure(Request $request)
    {
        return redirect()->route('subscription.index')
            ->with('error', 'El pago ha sido cancelado o ha fallado. Por favor, inténtalo de nuevo.');
    }

    /**
     * Generate invoice for subscription payment (Used by Webhook)
     */
    public function generateInvoice(Subscription $subscription, float $amount): Invoice
    {
        $taxRate = config('invoicing.tax_rate', 21.00);
        $subtotal = round($amount / (1 + ($taxRate / 100)), 2);
        $taxAmount = round($subtotal * ($taxRate / 100), 2);

        $invoice = Invoice::create([
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total_amount' => $amount,
            'issue_date' => now(),
            'status' => 'paid',
            'notes' => 'Pago realizado mediante Stripe',
        ]);

        $generator = app(InvoiceGeneratorService::class);
        $generator->generate($invoice);

        try {
            $this->holded->createInvoice($invoice);
        } catch (\Exception $e) {
            Log::error('Failed to sync invoice to Holded', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
        }

        return $invoice;
    }
}
