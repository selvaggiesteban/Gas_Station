<?php

namespace App\Actions;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SubscribeUserAction
{
    public function execute(User $user, Plan $plan, string $paymentId = null): Subscription
    {
        return DB::transaction(function () use ($user, $plan, $paymentId) {
            // Cancel any active subscriptions first
            Subscription::where('user_id', $user->id)
                ->where('status', 'active')
                ->update(['status' => 'cancelled']);

            return Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'starts_at' => Carbon::now(),
                'ends_at' => Carbon::now()->addDays($plan->duration_in_days),
                'status' => 'active',
                'payment_id' => $paymentId ?? (string) \Illuminate\Support\Str::uuid(),
            ]);
        });
    }
}
