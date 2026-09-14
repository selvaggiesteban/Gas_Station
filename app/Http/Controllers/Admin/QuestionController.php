<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Test;
use App\Models\Question;
use App\Models\Option;
use Illuminate\Http\Request;
use App\Http\Requests\Admin\StoreQuestionRequest;
use App\Http\Requests\Admin\UpdateQuestionRequest;

class QuestionController extends Controller
{
    public function store(StoreQuestionRequest $request, ?Test $test = null)
    {
        $testId = ($test && $test->id) ? $test->id : $request->test_id;

        if (!$testId) {
            return redirect()->back()->with('error', 'Falta el ID del test.');
        }

        $targetTest = Test::findOrFail($testId);

        $question = $targetTest->questions()->create([
            'text' => $request->text,
            'type' => $request->type,
            'max_score' => $request->points,
            'explanation' => $request->explanation,
            'fill_blank_text' => $request->fill_blank_text,
            'display_order' => ($targetTest->questions()->max('display_order') ?? 0) + 1,
        ]);

        if (in_array($request->type, ['multiple_choice', 'five_options']) && $request->has('options')) {
            foreach ($request->options as $optionData) {
                if (!empty($optionData['text'])) {
                    $question->options()->create([
                        'text' => $optionData['text'],
                        'is_correct' => $optionData['is_correct'] ?? false
                    ]);
                }
            }
        }

        return redirect()->back()->with('success', 'Pregunta añadida correctamente.');
    }

    public function update(UpdateQuestionRequest $request, Question $question)
    {
        $question->update([
            'text' => $request->text,
            'max_score' => $request->points,
            'explanation' => $request->explanation,
            'fill_blank_text' => $request->fill_blank_text,
        ]);

        if ($request->has('options')) {
            foreach ($request->options as $optionData) {
                if (isset($optionData['id'])) {
                    Option::where('id', $optionData['id'])->update([
                        'text' => $optionData['text'],
                        'is_correct' => $optionData['is_correct'] ?? false
                    ]);
                } else {
                    if (!empty($optionData['text'])) {
                        $question->options()->create([
                            'text' => $optionData['text'],
                            'is_correct' => $optionData['is_correct'] ?? false
                        ]);
                    }
                }
            }
        }

        return redirect()->back()->with('success', 'Pregunta actualizada.');
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
        ]);

        foreach ($request->ids as $index => $id) {
            Question::where('id', $id)->update(['display_order' => $index + 1]);
        }

        return response()->json(['success' => true]);
    }

    public function destroy(Question $question)
    {
        $question->delete();
        return redirect()->back()->with('success', 'Pregunta eliminada.');
    }
}
