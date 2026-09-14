<?php

namespace App\Http\Requests\ContentManager;

use Illuminate\Foundation\Http\FormRequest;

class QuestionChallengeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['Admin', 'Content Manager']);
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => 'required_if:action,reject|nullable|string|max:500',
            'action' => 'required|in:approve,reject',
        ];
    }
}
