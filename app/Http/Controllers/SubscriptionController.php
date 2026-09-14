<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    /**
     * Show subscription dashboard and plans
     */
    public function index()
    {
        $plans = Plan::all();
        $user = Auth::user();
        $currentSubscription = $user->subscription; 
        $invoices = $user->invoices()->latest()->get();
        
        return view('subscription.index', compact('plans', 'currentSubscription', 'invoices'));
    }

    /**
     * Initiate subscription process (Redirect to PaymentController)
     */
    public function subscribe(Request $request, Plan $plan)
    {
        return redirect()->route('pago.mostrar', $plan);
    }

    /**
     * Cancel active subscription via Stripe
     */
    public function cancel(Request $request)
    {
        $user = Auth::user();
        $subscription = $user->subscription;

        if (!$subscription || !$subscription->isActive()) {
            return back()->with('error', 'No tienes una suscripción activa para cancelar.');
        }

        try {
            $stripe = app(\App\Services\StripeService::class);
            
            if ($subscription->stripe_subscription_id) {
                $result = $stripe->cancelSubscription($subscription->stripe_subscription_id);
                
                if ($result['success']) {
                    $subscription->update(['status' => 'cancelled']);
                    return back()->with('success', 'Suscripción cancelada correctamente.');
                }
            }

            // Fallback for manual or non-stripe subscriptions
            $subscription->update(['status' => 'cancelled']);
            return back()->with('success', 'Suscripción marcada como cancelada.');

        } catch (\Exception $e) {
            \Log::error('Subscription Cancellation Error', ['error' => $e->getMessage()]);
            return back()->with('error', 'Ocurrió un error al intentar cancelar la suscripción.');
        }
    }
}
