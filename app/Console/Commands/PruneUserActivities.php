<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserActivity;

class PruneUserActivities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activity:prune';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete user activities older than 6 months, keeping critical events.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting activity pruning...');

        $count = UserActivity::where('created_at', '<', now()->subMonths(6))
            ->whereNotIn('action_type', ['submit', 'payment', 'download'])
            ->delete();

        $this->info("Successfully pruned {$count} activity records.");
    }
}
