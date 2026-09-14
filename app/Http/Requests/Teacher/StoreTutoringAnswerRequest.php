<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class StoreTutoringAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['Admin', 'Teacher', 'Content Manager']);
    }

    public function rules(): array
    {
        return [
            'answer_text' => 'required|string|min:10|max:600',
        ];
    }

    public function messages(): array
    {
        return [
            'answer_text.required' => 'La respuesta es obligatoria.',
            'answer_text.min' => 'La respuesta debe tener al menos :min caracteres.',
            'answer_text.max' => 'La respuesta no puede superar los :max caracteres.',
        ];
    }
}
