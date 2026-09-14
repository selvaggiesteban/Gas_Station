<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TopicController extends Controller
{
    /**
     * Display the specified topic.
     */
    public function show(Topic $topic)
    {
        $course = $topic->block->course;

        // 1. Check Course Authorization (Subscription)
        if (!auth()->user()->hasAccessToCourse($course)) {
            return redirect()->route('cursos.ver', $course)
                ->with('error', 'Se requiere una suscripción activa para acceder a este contenido.');
        }

        // 2. Check Drip Content availability
        if (!$topic->isAvailable() && !auth()->user()->hasRole(['Admin', 'Content Manager', 'Teacher'])) {
            $timeUntil = $topic->getTimeUntilAvailable();
            return redirect()->route('cursos.ver', $course)
                ->with('error', "Este contenido estará disponible {$timeUntil}");
        }

        return view('courses.topic', compact('topic'));
    }
}
