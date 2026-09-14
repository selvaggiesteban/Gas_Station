<?php

namespace App\Actions;

use App\Models\Battle;
use App\Models\Question;
use App\Models\Topic;

class AcceptBattleAction
{
    public function execute(Battle $battle): void
    {
        $topic = $battle->topic;
        $courseId = $topic->block->course_id ?? null;

        $query = Question::where('type', 'multiple_choice');

        if ($courseId) {
            $query->whereHas('test', function($q) use ($courseId) {
                $q->where('course_id', $courseId);
            });
        }

        $questionIds = $query->inRandomOrder()
            ->limit(10)
            ->pluck('id')
            ->toArray();
            
        // Fallback if no questions in course
        if (empty($questionIds)) {
            $questionIds = Question::where('type', 'multiple_choice')
                ->inRandomOrder()
                ->limit(10)
                ->pluck('id')
                ->toArray();
        }

        $battle->update([
            'status' => 'accepted',
            'question_ids' => json_encode($questionIds)
        ]);
    }
}
