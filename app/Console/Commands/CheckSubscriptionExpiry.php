<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckSubscriptionExpiry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:check-expiry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks for expired subscriptions and updates user status accordingly';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expiredSubscriptions = Subscription::where('status', 'active')
            ->where('ends_at', '<', now())
            ->get();

        $count = 0;
        foreach ($expiredSubscriptions as $subscription) {
            $subscription->update(['status' => 'expired']);
            
            // Log the expiry for audit
            Log::info("Subscription #{$subscription->id} for User #{$subscription->user_id} has expired.");
            $count++;
        }

        $this->info("Successfully processed {$count} expired subscriptions.");
    }
}
