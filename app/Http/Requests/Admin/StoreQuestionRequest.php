<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['Admin', 'Content Manager']);
    }

    public function rules(): array
    {
        return [
            'test_id' => 'required|exists:tests,id',
            'text' => 'required|string',
            'type' => 'required|in:multiple_choice,five_options,fill_blank,open_ended,practical_case',
            'points' => 'required|numeric|min:0',
            'explanation' => 'nullable|string',
            'options' => 'required_if:type,multiple_choice,five_options|array|min:2',
            'options.*.text' => 'required|string',
            'options.*.is_correct' => 'required|boolean',
            'fill_blank_text' => 'required_if:type,fill_blank|nullable|string',
        ];
    }
}
