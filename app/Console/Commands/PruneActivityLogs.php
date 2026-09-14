<?php

namespace App\Console\Commands;

use App\Models\UserActivity;
use Illuminate\Console\Command;

class PruneActivityLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:prune-activity-logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete user activity logs older than 6 months';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = UserActivity::where('created_at', '<', now()->subMonths(6))->delete();

        $this->info("Successfully deleted {$count} activity log records.");
    }
}
