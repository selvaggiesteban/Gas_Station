<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\TutoringQuestion;
use App\Http\Requests\Teacher\StoreTutoringAnswerRequest;
use Illuminate\Http\Request;

class TutoringAnswerController extends Controller
{
    public function pending()
    {
        $questions = TutoringQuestion::where('status', 'pending')
            ->with(['user', 'topic'])
            ->latest()
            ->paginate(15);

        return view('teacher.tutoring.pending', compact('questions'));
    }

    public function show(TutoringQuestion $question)
    {
        $question->load(['user', 'topic']);
        return view('teacher.tutoring.show', compact('question'));
    }

    public function store(StoreTutoringAnswerRequest $request, TutoringQuestion $question)
    {
        $question->update([
            'answer' => $request->answer_text,
            'is_public' => false,
            'status' => 'answered',
            'answered_by' => auth()->id(),
            'answered_at' => now(),
        ]);

        // Notificar al alumno (N13)
        $question->user?->notify(new \App\Notifications\TutoringAnswered($question));

        return redirect()->route('teacher.tutoring.pending')
            ->with('success', 'Respuesta enviada correctamente.');
    }
}
