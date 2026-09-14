<?php

namespace App\Http\Controllers;

use App\Actions\MarkTopicCompletedAction;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TopicProgressController extends Controller
{
    public function store(Topic $topic, Request $request, MarkTopicCompletedAction $markCompleted)
    {
        $user = Auth::user();
        $phase = $request->input('phase');

        if ($phase) {
            $column = match ($phase) {
                'reading' => 'phase_reading',
                'underlining' => 'phase_underlining',
                'review' => 'phase_review',
                'fast_review' => 'phase_fast_review',
                // Allow direct column names too if they were used before
                'phase_reading' => 'phase_reading',
                'phase_underlining' => 'phase_underlining',
                'phase_review' => 'phase_review',
                'phase_fast_review' => 'phase_fast_review',
                default => null,
            };

            if ($column) {
                // Use the generic topics() relationship to include all progress phases
                $user->topics()->syncWithoutDetaching([
                    $topic->id => [$column => true]
                ]);

                // Check if all phases are completed to also set is_completed
                $pivot = $user->topics()->where('topic_id', $topic->id)->first()?->pivot;

                if ($pivot && $pivot->phase_reading && $pivot->phase_underlining && $pivot->phase_review && $pivot->phase_fast_review && !$pivot->is_completed) {
                    $markCompleted->execute($user, $topic);
                    
                    if ($request->wantsJson()) {
                        return response()->json([
                            'success' => true,
                            'message' => '¡Tema completado al finalizar todas las fases! (+50 XP)',
                            'completed' => true
                        ]);
                    }

                    return redirect()->back()->with('success', '¡Tema completado al finalizar todas las fases! (+50 XP)');
                }

                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Fase marcada como completada.',
                        'completed' => false
                    ]);
                }

                return redirect()->back()->with('success', 'Fase marcada como completada.');
            }
        }

        // Default behavior: complete the whole topic
        $markCompleted->execute($user, $topic);
        
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => '¡Tema completado! (+50 XP)',
                'completed' => true
            ]);
        }

        return redirect()->back()->with('success', '¡Tema completado! (+50 XP)');
    }
}
