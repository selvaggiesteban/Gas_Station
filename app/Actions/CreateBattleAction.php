<?php

namespace App\Actions;

use App\Models\Battle;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateBattleAction
{
    public function execute(array $data): Battle
    {
        return DB::transaction(function () use ($data) {
            // Validate not challenging self
            if ($data['opponent_id'] === Auth::id()) {
                throw new \DomainException('No puedes desafiarte a ti mismo');
            }

            // Check for existing pending battles between same users
            $existingBattle = Battle::where(function ($query) use ($data) {
                $query->where('challenger_id', Auth::id())
                    ->where('opponent_id', $data['opponent_id']);
            })->orWhere(function ($query) use ($data) {
                $query->where('challenger_id', $data['opponent_id'])
                    ->where('opponent_id', Auth::id());
            })
            ->where('status', 'pending')
            ->first();

            if ($existingBattle) {
                throw new \DomainException('Ya existe una batalla pendiente con este usuario');
            }

            // Determinar el topic: el del challenger o uno aleatorio
            $topicId = $data['topic_id'] ?? \App\Models\Topic::inRandomOrder()->first()?->id;

            // Generar 10 preguntas aleatorias del topic (múltiple choice o five_options)
            $questionIds = [];
            if ($topicId) {
                $questionIds = \App\Models\Question::where('topic_id', $topicId)
                    ->whereIn('type', ['multiple_choice', 'five_options'])
                    ->inRandomOrder()
                    ->limit(10)
                    ->pluck('id')
                    ->toArray();
            }

            // Si no hay 10 preguntas del topic, completar con aleatorias globales
            if (count($questionIds) < 10) {
                $needed = 10 - count($questionIds);
                $extra = \App\Models\Question::whereIn('type', ['multiple_choice', 'five_options'])
                    ->whereNotIn('id', $questionIds)
                    ->inRandomOrder()
                    ->limit($needed)
                    ->pluck('id')
                    ->toArray();
                $questionIds = array_merge($questionIds, $extra);
            }

            return Battle::create([
                'challenger_id' => Auth::id(),
                'opponent_id' => $data['opponent_id'],
                'topic_id' => $topicId,
                'status' => 'pending',
                'question_ids' => $questionIds ?: null,
            ]);
        });
    }
}
