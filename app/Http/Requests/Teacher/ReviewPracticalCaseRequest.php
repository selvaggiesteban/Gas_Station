<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class ReviewPracticalCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['Admin', 'Teacher', 'Content Manager']);
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:reviewed,challenged',
            'score' => 'required|integer|min:0|max:100',
            'feedback' => 'nullable|string|max:1000',
        ];
    }
}
