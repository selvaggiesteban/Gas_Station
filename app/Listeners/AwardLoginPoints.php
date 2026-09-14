<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Carbon\Carbon;

class AwardLoginPoints
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $user = $event->user;
        $now = Carbon::now();
        $lastLogin = $user->last_login_at;

        // Skip if already logged in today
        if ($lastLogin && $lastLogin->isToday()) {
            return;
        }

        // Award daily login points
        $pointsToAward = 2;
        $newStreak = 1;

        if ($lastLogin) {
            if ($lastLogin->isYesterday()) {
                $newStreak = $user->login_streak_days + 1;
                
                // Award streak bonuses
                if ($newStreak === 7) {
                    $pointsToAward += 20;
                } elseif ($newStreak === 30) {
                    $pointsToAward += 120;
                }
            }
        }

        $user->update([
            'last_login_at' => $now,
            'login_streak_days' => $newStreak,
        ]);

        // Award points with monthly tracking
        app(\App\Services\PointService::class)->award(
            $user, 
            $pointsToAward, 
            'login',
            null,
            null
        );
    }
}
