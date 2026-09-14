<?php

namespace App\Http\Controllers;

use App\Models\TutoringQuestion;
use App\Models\Course;
use App\Http\Requests\StoreTutoringRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TutoringController extends Controller
{
    /**
     * Display Q&A index for students
     */
    public function index()
    {
        $user = Auth::user();

        // Get user's questions
        $myQuestions = TutoringQuestion::where('user_id', $user->id)
            ->with(['course', 'topic', 'answerer'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Get public questions from all users
        $publicQuestions = TutoringQuestion::public()
            ->where('status', 'answered')
            ->with(['course', 'topic', 'answerer'])
            ->orderBy('answered_at', 'desc')
            ->take(20)
            ->get();

        // Get courses for new question form
        $courses = Course::where('is_active', true)->get();

        return view('tutoring.index', compact('myQuestions', 'publicQuestions', 'courses'));
    }

    /**
     * Store a new question
     */
    public function store(StoreTutoringRequest $request)
    {
        $topic = \App\Models\Topic::with('block')->find($request->topic_id);
        
        if (!$topic) {
            return redirect()->back()->with('error', 'El tema seleccionado no es válido.');
        }

        $courseId = $request->course_id ?? $topic->block->course_id;

        $question = TutoringQuestion::create([
            'user_id' => Auth::id(),
            'course_id' => $courseId,
            'topic_id' => $request->topic_id,
            'title' => strip_tags($request->title),
            'content' => strip_tags($request->content),
            'is_public' => $request->boolean('is_public', false),
            'status' => 'pending',
        ]);

        return redirect()->route('tutorias.lista')
            ->with('success', 'Tu pregunta ha sido enviada. Un profesor la responderá pronto.');
    }

    /**
     * Show a specific question
     */
    public function show(TutoringQuestion $question)
    {
        // Check if user can view this question
        if (!$question->is_public && $question->user_id !== Auth::id() && !Auth::user()->hasAnyRole(['Teacher', 'Admin', 'Content Manager'])) {
            abort(403);
        }

        $question->load(['user', 'course', 'topic', 'answerer']);

        return view('tutoring.show', compact('question'));
    }

    /**
     * Mark question as resolved by student
     */
    public function resolve(TutoringQuestion $question)
    {
        if ($question->user_id !== Auth::id()) {
            abort(403);
        }

        $question->markAsResolved();

        return redirect()->route('tutorias.lista')
            ->with('success', 'Pregunta marcada como resuelta.');
    }
}
