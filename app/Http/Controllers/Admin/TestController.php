<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Test;
use Illuminate\Http\Request;
use App\Http\Requests\Admin\StoreTestRequest;
use App\Http\Requests\Admin\UpdateTestRequest;

class TestController extends Controller
{
    public function store(StoreTestRequest $request, Course $course = null)
    {
        $courseId = ($course && $course->id) ? $course->id : $request->course_id;

        if (!$courseId) {
            return redirect()->back()->with('error', 'Falta vincular el ID del curso.');
        }

        $test = Test::create(array_merge($request->validated(), [
            'course_id' => $courseId
        ]));

        return redirect()->back()->with('success', 'Examen creado correctamente.');
    }

    public function show(Test $test)
    {
        $test->load(['questions.options', 'course']);
        return view('admin.tests.show', compact('test'));
    }

    public function update(UpdateTestRequest $request, Test $test)
    {
        $test->update($request->validated());

        return redirect()->back()->with('success', 'Examen actualizado.');
    }

    public function destroy(Test $test)
    {
        $test->delete();
        return redirect()->back()->with('success', 'Examen eliminado.');
    }
}
