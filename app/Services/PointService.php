<?php

namespace App\Services;

use App\Models\User;
use App\Models\PointLog;
use App\Actions\CheckLevelUpAction;
use Illuminate\Support\Facades\DB;

class PointService
{
    /**
     * Award points to a user and log it.
     */
    public function award(User $user, int $amount, string $type, ?string $refType = null, ?int $refId = null): void
    {
        if ($amount <= 0) return;

        DB::transaction(function() use ($user, $amount, $type, $refType, $refId) {
            // Update user total points
            $user->increment('points', $amount);

            // Log points for monthly and history tracking
            PointLog::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'type' => $type,
                'reference_type' => $refType,
                'reference_id' => $refId,
            ]);

            // Check for level up
            app(CheckLevelUpAction::class)->execute($user);
        });
    }

    /**
     * Award points based on study time milestones.
     * 
     * 10 a 20 horas: 20 pts.
     * 20 a 40 horas: 40 pts.
     * Más de 40 horas: 60 pts.
     */
    public function awardForStudyTime(User $user, float $totalHours): void
    {
        $milestones = [
            10 => 20,
            20 => 40,
            40 => 60,
        ];

        foreach ($milestones as $hours => $points) {
            if ($totalHours >= $hours) {
                // Check if this specific milestone has been awarded
                $alreadyAwarded = PointLog::where('user_id', $user->id)
                    ->where('type', 'study_time_milestone')
                    ->where('reference_id', $hours)
                    ->exists();

                if (!$alreadyAwarded) {
                    $this->award($user, $points, 'study_time_milestone', 'StudyMilestone', $hours);
                }
            }
        }
    }

    /**
     * Get monthly leaderboard
     */
    public function getMonthlyLeaders(int $limit = 10)
    {
        return $this->getMonthlyRanking($limit);
    }

    /**
     * Get monthly ranking based on PointLog for the current month.
     */
    public function getMonthlyRanking(int $limit = 10)
    {
        return User::select('users.id', 'users.name', 'users.avatar', 'users.level', DB::raw('SUM(point_logs.amount) as monthly_points'))
            ->join('point_logs', 'users.id', '=', 'point_logs.user_id')
            ->whereMonth('point_logs.created_at', now()->month)
            ->whereYear('point_logs.created_at', now()->year)
            ->groupBy('users.id', 'users.name', 'users.avatar', 'users.level')
            ->orderByDesc('monthly_points')
            ->take($limit)
            ->get();
    }
}
