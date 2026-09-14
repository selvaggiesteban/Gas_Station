<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\PracticalCaseSubmission;
use App\Models\TestAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class EssayGradingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware('role:Teacher|Admin|Content Manager'),
        ];
    }

    public function index()
    {
        $submissions = PracticalCaseSubmission::where('status', 'submitted')
            ->with(['user', 'question', 'testAttempt.test'])
            ->latest()
            ->paginate(15);

        return view('teacher.essays.index', compact('submissions'));
    }

    public function grade(Request $request, PracticalCaseSubmission $submission)
    {
        $validated = $request->validate([
            'score' => 'required|numeric|min:0',
            'feedback' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $submission) {
            $submission->update([
                'score' => $validated['score'],
                'feedback' => $validated['feedback'],
                'status' => 'graded',
                'graded_by' => auth()->id(),
                'graded_at' => now(),
            ]);

            // Check if all submissions for this test attempt are graded
            $attempt = $submission->testAttempt;
            if ($attempt) {
                $pendingCount = PracticalCaseSubmission::where('test_attempt_id', $attempt->id)
                    ->where('status', 'submitted')
                    ->count();

                if ($pendingCount === 0) {
                    $this->finalizeAttemptScore($attempt);
                }
            }
        });

        return redirect()->route('teacher.essays.index')->with('success', 'Pregunta calificada correctamente.');
    }

    private function finalizeAttemptScore(TestAttempt $attempt)
    {
        $test = $attempt->test;
        $questions = $test->questions;
        
        $totalPointsPossible = $questions->sum('points');
        $totalPointsEarned = 0;

        $answers = $attempt->answers; // JSON attribute

        foreach ($questions as $question) {
            if (in_array($question->type, ['multiple_choice', 'five_options', 'fill_blank'])) {
                $userAnswer = $answers[$question->id]['answer'] ?? null;
                
                if ($question->type === 'fill_blank') {
                    if ($this->isFillBlankCorrect($userAnswer, $question->fill_blank_text)) {
                        $totalPointsEarned += $question->points;
                    }
                } else {
                    $option = \App\Models\Option::find($userAnswer);
                    if ($option && $option->is_correct) {
                        $totalPointsEarned += $question->points;
                    }
                }
            } else {
                // For manual graded, we look at the PracticalCaseSubmission
                $submission = PracticalCaseSubmission::where('test_attempt_id', $attempt->id)
                    ->where('question_id', $question->id)
                    ->first();
                
                if ($submission) {
                    $totalPointsEarned += $submission->score;
                }
            }
        }

        $finalScore = ($totalPointsPossible > 0) ? ($totalPointsEarned / $totalPointsPossible) * 100 : 0;
        
        $attempt->update([
            'score' => $finalScore,
            'passed' => $finalScore >= $test->passing_score,
        ]);

        $user = $attempt->user;
        if ($user) {
            $user->increment('total_tests_completed');
            $user->increment('total_supuestos_submitted');
            
            // Recompensas de Gamificación (Test + Supuestos)
            $pointsToAward = 50; // Base: 10 test + 40 supuesto
            
            if ($finalScore >= 90) {
                $pointsToAward += 160; // 50 test + 110 supuesto
            } elseif ($finalScore >= 80) {
                $pointsToAward += 115; // 35 test + 80 supuesto
            } elseif ($finalScore >= 70) {
                $pointsToAward += 75; // 20 test + 55 supuesto
            } elseif ($finalScore >= 60) {
                $pointsToAward += 45; // 10 test + 35 supuesto
            } elseif ($finalScore >= 50) {
                $pointsToAward += 25; // 5 test + 20 supuesto
            }

            $user->increment('points', $pointsToAward);
            app(\App\Actions\CheckLevelUpAction::class)->execute($user);
        }
    }

    private function isFillBlankCorrect($userAnswer, $correctAnswer): bool
    {
        if (empty($userAnswer) || empty($correctAnswer)) return false;
        
        $normUser = strtolower(trim($userAnswer));
        $normCorrect = strtolower(trim($correctAnswer));
        
        return $normUser === $normCorrect;
    }
}
