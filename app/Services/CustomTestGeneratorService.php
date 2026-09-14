<?php

namespace App\Services;

use App\Models\CustomTest;
use App\Models\Question;
use App\Models\Topic;
use App\Models\Block;
use App\Models\Test;
use Illuminate\Support\Facades\Log;

class CustomTestGeneratorService
{
    /**
     * Generate questions for a custom test (v18.0 Fixed - No more SQL 500)
     */
    public function generate(CustomTest $customTest): array
    {
        try {
            $query = Question::query();

            // Sincronización de Ingeniería: 
            // Las preguntas no tienen topic_id, pertenecen a tests.
            // Obtenemos los tests que pertenecen a los temas seleccionados.
            $testIds = collect();

            if (!empty($customTest->topics)) {
                $testIds = Test::whereIn('course_id', function($q) use ($customTest) {
                    $q->select('course_id')->from('blocks')->whereIn('id', function($q2) use ($customTest) {
                        $q2->select('block_id')->from('topics')->whereIn('id', $customTest->topics);
                    });
                })->pluck('id');
            } elseif (!empty($customTest->blocks)) {
                $testIds = Test::whereIn('course_id', function($q) use ($customTest) {
                    $q->select('course_id')->from('blocks')->whereIn('id', $customTest->blocks);
                })->pluck('id');
            }

            if ($testIds->isNotEmpty()) {
                $query->whereIn('test_id', $testIds);
            }

            // Filter by question types
            if (!empty($customTest->question_types)) {
                $query->whereIn('type', $customTest->question_types);
            }

            // Get available questions
            $availableQuestions = $query->inRandomOrder()
                ->limit($customTest->question_count)
                ->get();

            if ($availableQuestions->count() < 5) {
                return [
                    'success' => false,
                    'message' => "No hay suficientes preguntas en el banco para los criterios seleccionados.",
                ];
            }

            $questionIds = $availableQuestions->pluck('id')->toArray();
            $customTest->markAsGenerated($questionIds);

            return [
                'success' => true,
                'questions' => $questionIds,
            ];

        } catch (\Exception $e) {
            Log::error('Custom test generation failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Fallo técnico en el banco de preguntas.'];
        }
    }
}
