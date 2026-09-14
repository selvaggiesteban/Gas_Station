<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subscription;
use App\Jobs\ProcessSubscriptionRenewalJob;
use Illuminate\Support\Facades\Log;

class RetryFailedSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:retry-failed
                            {--subscription= : Specific subscription ID to retry}
                            {--dry-run : Show what would be retried without actually retrying}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry failed subscription renewals';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $subscriptionId = $this->option('subscription');
        $isDryRun = $this->option('dry-run');

        if ($subscriptionId) {
            // Retry specific subscription
            $subscription = Subscription::find($subscriptionId);

            if (!$subscription) {
                $this->error("Subscription #{$subscriptionId} not found");
                return 1;
            }

            if (!$subscription->canRenew()) {
                $this->error("Subscription #{$subscriptionId} cannot be renewed");
                $this->line("Status: {$subscription->status}");
                $this->line("Failures: {$subscription->payment_failure_count}");
                return 1;
            }

            $this->info("Retrying subscription #{$subscriptionId}");

            if (!$isDryRun) {
                // Reset failure count to allow retry
                $subscription->update([
                    'payment_failure_count' => 0,
                    'last_payment_error' => null,
                ]);

                ProcessSubscriptionRenewalJob::dispatch($subscription);
                $this->info("Job dispatched successfully");
            } else {
                $this->warn('DRY RUN - Job would be dispatched');
            }

            return 0;
        }

        // Retry all failed subscriptions
        $failedSubscriptions = Subscription::query()
            ->where('status', 'payment_failed')
            ->whereNotNull('payment_token')
            ->where(function ($q) {
                $q->where('payment_failure_count', '<', 3)
                  ->orWhereNull('payment_failure_count');
            })
            ->get();

        $this->info("Found {$failedSubscriptions->count()} failed subscriptions to retry");

        if ($failedSubscriptions->isEmpty()) {
            $this->warn('No failed subscriptions found');
            return 0;
        }

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No actual retries will be processed');
            foreach ($failedSubscriptions as $subscription) {
                $this->line("Would retry subscription #{$subscription->id} for user {$subscription->user->email}");
                $this->line("  - Plan: {$subscription->plan->name}");
                $this->line("  - Failures: {$subscription->payment_failure_count}");
                $this->line("  - Last error: {$subscription->last_payment_error}");
            }
            return 0;
        }

        $dispatched = 0;
        $progress = $this->output->createProgressBar($failedSubscriptions->count());
        $progress->start();

        foreach ($failedSubscriptions as $subscription) {
            $progress->advance();

            // Reset failure count for fresh retry
            $subscription->update([
                'payment_failure_count' => 0,
                'last_payment_error' => null,
                'status' => Subscription::STATUS_ACTIVE,
            ]);

            ProcessSubscriptionRenewalJob::dispatch($subscription);
            $dispatched++;
        }

        $progress->finish();
        $this->newLine();

        $this->info("Retry complete: {$dispatched} subscriptions queued for renewal");

        Log::info("RetryFailedSubscriptions command completed", [
            'dispatched' => $dispatched,
            'total_failed' => $failedSubscriptions->count(),
        ]);

        return 0;
    }
}
