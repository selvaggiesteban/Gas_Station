<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Invoice;
use App\Services\StripeService;
use App\Services\HoldedService;
use App\Mail\NewAccountCreated;
use App\Mail\OrderPaid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class StripeWebhookController extends Controller
{
    private HoldedService $holded;

    public function __construct(HoldedService $holded)
    {
        $this->holded = $holded;
    }

    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $stripe = app(StripeService::class);
            $result = $stripe->validateWebhook($payload, $sigHeader);
        } catch (\Error $e) {
            Log::error('Stripe SDK not installed for Webhook', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Stripe SDK not available'], 500);
        }

        if (!$result['success']) {
            return response()->json(['error' => $result['message']], 400);
        }

        $event = $result['event'];

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $this->processSuccessfulOrder($session);
        }

        return response()->json(['status' => 'success']);
    }

    protected function processSuccessfulOrder($session)
    {
        $order = Order::where('stripe_session_id', $session->id)->first();

        if (!$order) {
            Log::error('Order not found for Stripe session', ['session_id' => $session->id]);
            return;
        }

        if ($order->status === 'paid') {
            return;
        }

        // 1. Find or Create User
        $user = User::where('email', $order->email)->first();

        if (!$user) {
            $password = Str::random(12);
            $user = User::create([
                'name' => $order->name,
                'email' => $order->email,
                'password' => Hash::make($password),
                'nif' => $order->nif,
                'phone' => $order->phone,
                'address' => $order->address,
                'city' => $order->city,
                'province' => $order->province,
                'postal_code' => $order->postal_code,
                'company_name' => $order->company_name,
            ]);

            $user->assignRole('Student');
            
            // Send Welcome Email
            Mail::to($user->email)->send(new NewAccountCreated($user, $password));
        } else {
            $user->update([
                'nif' => $user->nif ?? $order->nif,
                'phone' => $user->phone ?? $order->phone,
                'address' => $user->address ?? $order->address,
                'city' => $user->city ?? $order->city,
                'province' => $user->province ?? $order->province,
                'postal_code' => $user->postal_code ?? $order->postal_code,
            ]);
        }

        // 2. Update Order
        $order->update([
            'user_id' => $user->id,
            'status' => 'paid',
        ]);

        // 3. Increment Coupon usage
        if ($order->coupon) {
            $order->coupon->apply();
        }

        // 4. Create Subscriptions
        foreach ($order->items as $item) {
            Subscription::create([
                'user_id' => $user->id,
                'product_id' => $item->product_id,
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => now()->addDays($item->product->duration_days ?? config('academy.default_subscription_days')),
                'payment_id' => $session->payment_intent,
                'payment_provider' => 'stripe',
                'stripe_subscription_id' => $session->subscription ?? null,
            ]);
        }

        // Send Order Confirmation
        Mail::to($user->email)->send(new OrderPaid($order));

        // 4. Invoicing
        $this->generateAndSyncInvoice($order, $user);
    }

    protected function generateAndSyncInvoice(Order $order, User $user)
    {
        $invoice = Invoice::create([
            'user_id' => $user->id,
            'subtotal' => $order->subtotal,
            'tax_rate' => config('academy.tax_rate'),
            'tax_amount' => $order->tax_amount,
            'total_amount' => $order->total_amount,
            'issue_date' => now(),
            'status' => 'paid',
            'notes' => 'Pedido #' . $order->order_number . ' via Stripe',
        ]);

        try {
            $this->holded->createInvoice($invoice);
        } catch (\Exception $e) {
            Log::error('Holded sync failed', ['error' => $e->getMessage()]);
        }
    }
}
