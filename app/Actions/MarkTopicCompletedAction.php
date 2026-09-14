<?php

namespace App\Actions;

use App\Models\Topic;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MarkTopicCompletedAction
{
    public function execute(User $user, Topic $topic): void
    {
        DB::transaction(function () use ($user, $topic) {
            // Validate drip content availability
            if (!$topic->isAvailable()) {
                throw new \Exception('Este tema aún no está disponible');
            }

            // Check if already completed in the current round to avoid double points/XP
            $alreadyCompleted = DB::table('topic_user')
                ->where('user_id', $user->id)
                ->where('topic_id', $topic->id)
                ->where('is_completed', true)
                ->where('round_number', $user->current_study_round ?? 1)
                ->exists();

            if ($alreadyCompleted) {
                return;
            }

            $user->completedTopics()->syncWithoutDetaching([
                $topic->id => [
                    'is_completed' => true,
                    'completed_at' => Carbon::now(),
                    'round_number' => $user->current_study_round ?? 1
                ]
            ]);

            // Bonus: Add XP for completing a topic and increment classes viewed milestone
            $user->increment('total_classes_viewed');
            $user->increment('points', 50);

            // Check for level up
            app(CheckLevelUpAction::class)->execute($user);
        });
    }
}
