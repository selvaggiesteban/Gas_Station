<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use App\Http\Requests\Admin\StoreCourseRequest;
use App\Http\Requests\Admin\UpdateCourseRequest;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    public function index()
    {
        // Increase pagination and sort by latest to show new courses first
        $courses = Course::withCount(['blocks', 'tests'])->latest()->paginate(100);
        return view('admin.courses.index', compact('courses'));
    }

    public function create()
    {
        return view('admin.courses.create');
    }

    public function store(StoreCourseRequest $request)
    {
        Course::create($request->validated());

        return redirect()->route('admin.cursos.index')->with('success', 'Curso creado correctamente.');
    }

    public function show(Course $course)
    {
        $course->load(['blocks.topics', 'tests']);
        return view('admin.courses.show', compact('course'));
    }

    public function edit(Course $course)
    {
        return view('admin.courses.edit', compact('course'));
    }

    public function update(UpdateCourseRequest $request, Course $course)
    {
        $course->update($request->validated());

        return redirect()->route('admin.cursos.show', $course)->with('success', 'Curso actualizado correctamente.');
    }

    public function destroy(Course $course)
    {
        try {
            $course->delete(); // SoftDeletes: marca deleted_at, no borra filas

            return redirect()->route('admin.cursos.index')
                ->with('success', 'Curso movido a la papelera. Se puede restaurar contactando con soporte.');
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000') {
                return back()->with('error', 'No se puede eliminar: hay datos vinculados. Contacta con soporte técnico.');
            }
            throw $e;
        }
    }

    public function duplicate(Course $course)
    {
        try {
            $newCourse = $course->replicate();
            $newCourse->title = $course->title . ' (Copia)';
            $newCourse->slug = $course->slug . '-copia-' . time();
            $newCourse->save();

            // Duplicar bloques y temas
            foreach ($course->blocks as $block) {
                $newBlock = $block->replicate();
                $newBlock->course_id = $newCourse->id;
                $newBlock->save();

                foreach ($block->topics as $topic) {
                    $newTopic = $topic->replicate();
                    $newTopic->block_id = $newBlock->id;
                    $newTopic->slug = $topic->slug . '-copia-' . time() . '-' . rand(1, 999);
                    $newTopic->save();
                }
            }

            return redirect()->route('admin.cursos.index')->with('success', 'Curso duplicado con éxito.');
        } catch (\Exception $e) {
            \Log::error('Error duplicando curso: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al duplicar el curso: ' . $e->getMessage());
        }
    }
}
