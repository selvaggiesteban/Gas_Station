<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('perfil.editar')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Cancel any active subscriptions before deleting
        $this->cancelUserSubscriptions($user);

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::route('login');
    }

    /**
     * Cancel all active subscriptions for a user
     */
    private function cancelUserSubscriptions($user): void
    {
        try {
            // Get active subscription
            $subscription = $user->subscription;

            if ($subscription && $subscription->isActive()) {
                // Cancel with payment provider if needed
                if ($subscription->payment_provider === 'paypal' && $subscription->payment_token) {
                    $paypalService = app(\App\Services\PayPalService::class);
                    $paypalService->cancelSubscription($subscription->payment_token, 'Usuario eliminó su cuenta');
                }

                // Cancel in Stripe if applicable
                if ($subscription->payment_provider === 'stripe' && $subscription->payment_token) {
                    try {
                        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
                        \Stripe\Subscription::cancel($subscription->payment_token);
                    } catch (\Exception $e) {
                        \Log::error('Failed to cancel Stripe subscription on account deletion', [
                            'subscription_id' => $subscription->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // Mark as cancelled in our database
                $subscription->cancel();

                \Log::info('Subscription cancelled due to account deletion', [
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Error cancelling subscriptions during account deletion', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            // Don't throw - we still want to delete the account even if cancellation fails
        }
    }
}
