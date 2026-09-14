<?php

namespace App\Services;

use App\Models\User;
use App\Models\TestAttempt;
use App\Models\UserQuestionMetadata;
use Illuminate\Support\Facades\DB;

class AcademicStatsService
{
    /**
     * Calculate the percentile of a user compared to others.
     */
    public function calculatePercentile(User $user)
    {
        $userPoints = $user->points ?? 0;
        $totalUsers = User::role('Student')->count();
        
        if ($totalUsers <= 1) return 100;

        $usersBelow = User::role('Student')
            ->where('points', '<', $userPoints)
            ->count();

        return round(($usersBelow / ($totalUsers - 1)) * 100);
    }

    /**
     * Get study speed (average seconds per question).
     */
    public function getAverageSpeed(User $user)
    {
        $totalSeconds = TestAttempt::where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->sum('time_spent_seconds');

        // If time_spent_seconds is NULL for old attempts, fallback to diff
        if (!$totalSeconds) {
            $totalSeconds = TestAttempt::where('user_id', $user->id)
                ->whereNotNull('completed_at')
                ->select(DB::raw('SUM(TIMESTAMPDIFF(SECOND, started_at, completed_at)) as total_time'))
                ->value('total_time');
        }

        $totalQuestions = TestAttempt::where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->sum('questions_count');

        if (!$totalQuestions) return 0;

        return round($totalSeconds / $totalQuestions, 2);
    }

    /**
     * Get statistics about doubts vs correct answers.
     */
    public function getDoubtStats(User $user)
    {
        $totalDoubts = UserQuestionMetadata::where('user_id', $user->id)
            ->where('is_doubt', true)
            ->count();

        return [
            'total_doubts' => $totalDoubts,
            'resolved_correctly' => 0, // Placeholder
        ];
    }
}
