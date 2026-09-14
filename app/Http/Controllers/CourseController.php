<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;

class CourseController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Get courses the user has access to
        if ($user->hasRole(['Admin', 'Teacher', 'Content Manager'])) {
            $myCourses = Course::where('is_active', true)->get();
            $availableCourses = collect(); // Has all
        } else {
            // Student logic
            $myCourseIds = $user->subscriptions()
                ->where('status', 'active')
                ->where('ends_at', '>', now())
                ->with('product')
                ->get()
                ->pluck('product.course_id')
                ->filter()
                ->unique();

            $myCourses = Course::whereIn('id', $myCourseIds)->where('is_active', true)->get();
            $availableCourses = Course::whereNotIn('id', $myCourseIds)->where('is_active', true)->get();
        }

        return view('courses.index', compact('myCourses', 'availableCourses'));
    }

    public function show(\App\Models\Course $course)
    {
        $user = auth()->user();

        // Allow access if admin/teacher/content manager
        $hasAccess = $user->hasRole(['Admin', 'Teacher', 'Content Manager']);

        // Check if student has an active subscription for this course
        if (!$hasAccess) {
            // Simplified check: if they have any active subscription, allow access to assigned courses.
            // Adjust this logic if products map 1:1 to specific courses.
            $hasAccess = $user->subscriptions()
                ->where('status', 'active')
                ->where('ends_at', '>', now())
                ->exists();
        }

        if (!$hasAccess) {
            return redirect()->route('cursos.catalogo')->with('error', 'Necesitas una suscripción activa para acceder a este curso.');
        }

        $course->load('blocks.topics');
        return view('courses.show', compact('course'));
    }
}
