<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Services\RedsysService;
use App\Notifications\SubscriptionRenewalFailed;
use App\Notifications\SubscriptionRenewed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessSubscriptionRenewalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1 min, 5 min, 15 min

    public function __construct(
        public Subscription $subscription
    ) {
    }

    public function handle(RedsysService $redsys): void
    {
        if (!$this->subscription->canRenew()) {
            Log::info("Subscription #{$this->subscription->id} cannot be renewed. Skipping.");
            return;
        }

        Log::info("Processing renewal for subscription #{$this->subscription->id}");

        try {
            // Attempt to charge using stored payment token
            $paymentResult = $redsys->chargeWithToken(
                $this->subscription->payment_token,
                $this->subscription->plan->price,
                [
                    'description' => "Renovación {$this->subscription->plan->name}",
                    'subscription_id' => $this->subscription->id,
                ]
            );

            if ($paymentResult['success']) {
                $this->handleSuccessfulRenewal($paymentResult);
            } else {
                $this->handleFailedPayment($paymentResult['error'] ?? 'Unknown error');
            }
        } catch (\Exception $e) {
            Log::error("Exception during renewal: {$e->getMessage()}", [
                'subscription_id' => $this->subscription->id,
                'trace' => $e->getTraceAsString(),
            ]);

            $this->handleFailedPayment($e->getMessage());
        }
    }

    private function handleSuccessfulRenewal(array $paymentResult): void
    {
        // Update subscription
        $this->subscription->update([
            'starts_at' => now(),
            'ends_at' => now()->addDays($this->subscription->plan->duration_days),
            'status' => Subscription::STATUS_ACTIVE,
            'renewal_count' => ($this->subscription->renewal_count ?? 0) + 1,
            'last_renewed_at' => now(),
            'payment_failure_count' => 0,
            'last_payment_error' => null,
            'last_payment_id' => $paymentResult['payment_id'] ?? null,
        ]);

        // Notify user of successful renewal
        $this->subscription->user->notify(new SubscriptionRenewed($this->subscription));

        Log::info("Subscription #{$this->subscription->id} renewed successfully");
    }

    private function handleFailedPayment(string $error): void
    {
        $failureCount = ($this->subscription->payment_failure_count ?? 0) + 1;

        $this->subscription->update([
            'status' => Subscription::STATUS_PAYMENT_FAILED,
            'payment_failure_count' => $failureCount,
            'last_payment_error' => $error,
        ]);

        Log::warning("Subscription #{$this->subscription->id} payment failed (attempt #{$failureCount}): {$error}");

        // If this is the 3rd failure, suspend subscription
        if ($failureCount >= 3) {
            $this->subscription->update(['status' => Subscription::STATUS_SUSPENDED]);
            $this->subscription->user->notify(new SubscriptionRenewalFailed($this->subscription, $error));
            Log::error("Subscription #{$this->subscription->id} suspended after 3 failed attempts");
        } else {
            // Schedule retry
            $this->scheduleRetry($failureCount);
            $this->subscription->user->notify(new SubscriptionRenewalFailed($this->subscription, $error));
        }
    }

    private function scheduleRetry(int $failureCount): void
    {
        // Retry schedule: Day 1, Day 3, Day 5 after initial failure
        $retryDays = [1, 3, 5];
        $retryDay = $retryDays[$failureCount - 1] ?? 5;

        $retryDate = now()->addDays($retryDay);

        Log::info("Scheduling retry for subscription #{$this->subscription->id} in {$retryDay} days ({$retryDate})");

        // Dispatch job to retry at the specified date
        ProcessSubscriptionRenewalJob::dispatch($this->subscription)
            ->delay($retryDate);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Job failed permanently for subscription #{$this->subscription->id}: {$exception->getMessage()}");
    }
}
