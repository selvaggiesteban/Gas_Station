<?php

namespace App\Http\Requests\ContentManager;

use Illuminate\Foundation\Http\FormRequest;

class StoreAcademyScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['Admin', 'Content Manager']);
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'sync_to_students' => 'nullable|boolean',
            'topic_id' => 'nullable|exists:topics,id',
            'phase' => 'nullable|in:phase_1,phase_2,phase_3',
        ];
    }
}
