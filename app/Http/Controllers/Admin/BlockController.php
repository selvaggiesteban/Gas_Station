<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Block;
use Illuminate\Http\Request;
use App\Http\Requests\Admin\StoreBlockRequest;
use App\Http\Requests\Admin\UpdateBlockRequest;

class BlockController extends Controller
{
    public function store(StoreBlockRequest $request, Course $course = null)
    {
        // Resolve course from route or request
        $courseId = ($course && $course->id) ? $course->id : $request->course_id;

        if (!$courseId) {
             return redirect()->back()->with('error', 'Falta vincular el ID del curso.');
        }

        $targetCourse = Course::findOrFail($courseId);

        $targetCourse->blocks()->create([
            'title' => $request->title,
            'description' => $request->description,
            'order' => $request->order ?? ($targetCourse->blocks()->max('order') + 1),
        ]);

        return redirect()->back()->with('success', 'Bloque añadido correctamente.');
    }

    public function update(UpdateBlockRequest $request, Block $block)
    {
        $block->update($request->validated());

        return redirect()->back()->with('success', 'Bloque actualizado.');
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
        ]);

        foreach ($request->ids as $index => $id) {
            Block::where('id', $id)->update(['order' => $index + 1]);
        }

        return response()->json(['success' => true]);
    }

    public function destroy(Block $block)
    {
        try {
            $block->delete();
            return redirect()->back()->with('success', 'Bloque eliminado.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se puede eliminar el bloque: hay temas vinculados a otras actividades (ej. batallas).');
        }
    }
}
