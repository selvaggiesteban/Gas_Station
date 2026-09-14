<?php

namespace App\Http\Controllers\ContentManager;

use App\Http\Controllers\Controller;
use App\Models\QuestionChallenge;
use App\Http\Requests\ContentManager\QuestionChallengeRequest;
use Illuminate\Http\Request;

class QuestionChallengeController extends Controller
{
    public function index()
    {
        $challenges = QuestionChallenge::where('status', 'pending')
            ->with(['user', 'question'])
            ->latest()
            ->paginate(15);

        return view('content-manager.challenges.index', compact('challenges'));
    }

    public function show(QuestionChallenge $challenge)
    {
        $challenge->load(['user', 'question.test.course', 'question.options']);
        return view('content-manager.challenges.show', compact('challenge'));
    }

    public function approve(Request $request, QuestionChallenge $challenge)
    {
        $challenge->update([
            'status' => 'approved',
            'reviewer_notes' => $request->reviewer_notes,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return redirect()->route('content-manager.challenges.index')
            ->with('success', 'Impugnación aprobada. Proceder a corregir la pregunta.');
    }

    public function reject(Request $request, QuestionChallenge $challenge)
    {
        $challenge->update([
            'status' => 'rejected',
            'reviewer_notes' => $request->reviewer_notes,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return redirect()->route('content-manager.challenges.index')
            ->with('success', 'Impugnación rechazada.');
    }

    public function history()
    {
        $challenges = QuestionChallenge::where('status', '!=', 'pending')
            ->with(['user', 'question', 'reviewer'])
            ->latest()
            ->paginate(20);

        return view('content-manager.challenges.history', compact('challenges'));
    }
}
