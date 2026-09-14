<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\TestAttempt;
use App\Models\TestAnswer;
use Illuminate\Http\Request;

class EssayReviewController extends Controller
{
    public function index()
    {
        $pendingAnswers = TestAnswer::where('status', 'pending_review')
            ->with(['question', 'testAttempt.user', 'testAttempt.test'])
            ->paginate(20);
            
        return view('teacher.essays.index', compact('pendingAnswers'));
    }

    public function show(TestAnswer $answer)
    {
        $this->authorize('review', $answer);
        $answer->load(['question', 'testAttempt.user', 'testAttempt.test']);
        return view('teacher.essays.show', compact('answer'));
    }

    public function grade(Request $request, TestAnswer $answer)
    {
        $this->authorize('review', $answer);

        $validated = $request->validate([
            'is_correct' => 'required|boolean',
            'feedback' => 'nullable|string',
        ]);

        $answer->update([
            'is_correct' => $validated['is_correct'],
            'feedback' => $validated['feedback'],
            'status' => 'reviewed',
        ]);

        // Recalculate the overall test score
        $this->recalculateTestScore($answer->testAttempt);

        return redirect()->route('teacher.essays.index')->with('success', 'Respuesta calificada y nota del test actualizada.');
    }
    
    private function recalculateTestScore(TestAttempt $attempt)
    {
        $attempt->load('answers');
        $correctAnswers = $attempt->answers->where('is_correct', true)->count();
        $totalQuestions = $attempt->answers->count();
        
        $score = ($totalQuestions > 0) ? ($correctAnswers / $totalQuestions) * 100 : 0;
        
        $attempt->update(['score' => round($score)]);
        
        // Optionally, send a notification to the user that their test has been graded
        // $attempt->user->notify(new TestGradedNotification($attempt));
    }
}
