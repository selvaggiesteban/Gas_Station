<?php

namespace App\Observers;

use App\Models\User;
use App\Models\WeeklyChallenge;
use App\Models\ChallengeParticipation;

class UserActivityObserver
{
    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // Check if total_classes_viewed was changed (incremented)
        if ($user->wasChanged('total_classes_viewed')) {
            $this->updateTopicChallenges($user);
        }
    }

    /**
     * Update progress for active topic-related challenges
     */
    private function updateTopicChallenges(User $user): void
    {
        // Find active challenges that track completed topics
        $activeChallenges = WeeklyChallenge::current()
            ->where('goal_type', WeeklyChallenge::GOAL_TOPICS_COMPLETED)
            ->get();

        foreach ($activeChallenges as $challenge) {
            $participation = $challenge->getOrCreateParticipation($user->id);
            
            if (!$participation->is_completed) {
                // Update progress by 1 for each topic completed
                $participation->updateProgress(1);
            }
        }
    }
}
