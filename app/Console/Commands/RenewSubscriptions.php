<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subscription;
use App\Jobs\ProcessSubscriptionRenewalJob;
use Illuminate\Support\Facades\Log;

class RenewSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:renew
                            {--dry-run : Show what would be renewed without actually renewing}
                            {--force : Force renewal even if not expired}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Renew expired subscriptions with stored payment tokens';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $force = $this->option('force');

        $query = Subscription::query()
            ->whereIn('status', ['active', 'payment_failed'])
            ->whereNotNull('payment_token')
            ->where(function ($q) {
                $q->where('payment_failure_count', '<', 3)
                  ->orWhereNull('payment_failure_count');
            });

        if ($force) {
            // Force mode: process all renewable subscriptions
            $subscriptions = $query->get();
        } else {
            // Normal mode: only process expired ones
            $subscriptions = $query->where(function ($q) {
                $q->where('ends_at', '<', now())
                  ->orWhereNull('ends_at');
            })->get();
        }

        $this->info("Found {$subscriptions->count()} subscriptions to process");

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No actual renewals will be processed');
            foreach ($subscriptions as $subscription) {
                $this->line("Would renew subscription #{$subscription->id} for user {$subscription->user->email}");
                $this->line("  - Plan: {$subscription->plan->name}");
                $endsAt = $subscription->ends_at ? $subscription->ends_at->format('Y-m-d H:i:s') : 'N/A';
                $this->line("  - Ends: {$endsAt}");
                $this->line("  - Failures: {$subscription->payment_failure_count}");
            }
            return 0;
        }

        $dispatched = 0;

        $progress = $this->output->createProgressBar($subscriptions->count());
        $progress->start();

        foreach ($subscriptions as $subscription) {
            $progress->advance();

            // Skip if subscription can not be renewed
            if (!$subscription->canRenew()) {
                $this->newLine();
                $this->warn("Skipping subscription #{$subscription->id} - cannot be renewed");
                continue;
            }

            // Dispatch job for async processing
            ProcessSubscriptionRenewalJob::dispatch($subscription);
            $dispatched++;
        }

        $progress->finish();
        $this->newLine();

        $this->info("Renewal processing complete: {$dispatched} jobs dispatched");
        $this->info("Jobs will be processed by queue workers.");
        $this->line("Run 'php artisan queue:work' to process them.");

        Log::info("RenewSubscriptions command completed", [
            'dispatched' => $dispatched,
            'total_found' => $subscriptions->count(),
        ]);

        return 0;
    }
}
