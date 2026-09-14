<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserRankObserver
{
    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        if ($user->wasChanged('points')) {
            $this->checkRankPromotion($user);
        }
    }

    /**
     * Check and promote user based on points.
     */
    private function checkRankPromotion(User $user): void
    {
        $points = $user->points;
        $newRank = 'Cobre';

        if ($points >= 9000) {
            $newRank = 'Diamante';
        } elseif ($points >= 6000) {
            $newRank = 'Oro';
        } elseif ($points >= 3000) {
            $newRank = 'Plata';
        } elseif ($points >= 1000) {
            $newRank = 'Bronce';
        }

        if ($user->rank_name !== $newRank) {
            $oldRank = $user->rank_name;
            $user->rank_name = $newRank;
            $user->saveQuietly();
            
            Log::info("User #{$user->id} promoted from {$oldRank} to {$newRank}.");
        }
    }
}
