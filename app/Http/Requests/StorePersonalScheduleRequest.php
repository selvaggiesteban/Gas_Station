<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePersonalScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'topic_id' => 'nullable|exists:topics,id',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El título es obligatorio.',
            'start_time.required' => 'La fecha de inicio es obligatoria.',
            'end_time.required' => 'La fecha de fin es obligatoria.',
            'end_time.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
            'topic_id.exists' => 'El tema seleccionado no existe.',
        ];
    }
}
