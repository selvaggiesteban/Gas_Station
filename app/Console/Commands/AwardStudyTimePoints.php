<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\PointService;

class AwardStudyTimePoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'award:study-points';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Award points to users based on their total study time milestones';

    /**
     * Execute the console command.
     */
    public function handle(PointService $pointService)
    {
        $this->info('Starting study points awarding process...');

        $users = User::role('Student')->get();
        $count = 0;

        foreach ($users as $user) {
            $totalHours = ($user->total_study_minutes ?? 0) / 60;
            if ($totalHours >= 10) {
                $pointService->awardForStudyTime($user, $totalHours);
                $count++;
            }
        }

        $this->info("Finished. Processed {$count} users with significant study time.");
    }
}
