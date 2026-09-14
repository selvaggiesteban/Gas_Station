<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTutoringRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_id' => 'nullable|exists:courses,id',
            'topic_id' => 'required|exists:topics,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:5000',
            'is_public' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Debes escribir un título para tu duda.',
            'content.required' => 'Debes detallar tu pregunta.',
            'topic_id.required' => 'Debes seleccionar un tema relacionado.',
        ];
    }
}
