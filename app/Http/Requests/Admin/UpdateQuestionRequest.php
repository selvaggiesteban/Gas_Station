<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['Admin', 'Content Manager']);
    }

    public function rules(): array
    {
        return [
            'text' => 'required|string',
            'points' => 'required|numeric|min:0',
            'explanation' => 'nullable|string',
            'options' => 'nullable|array',
            'options.*.id' => 'nullable|exists:options,id',
            'options.*.text' => 'required|string',
            'options.*.is_correct' => 'required|boolean',
            'fill_blank_text' => 'nullable|string',
        ];
    }
}
