<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreWeeklyChallengeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['Admin', 'Content Manager']);
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'goal_type' => 'required|in:points,pomodoros,tests_completed,battles_won,topics_completed',
            'goal_amount' => 'required|integer|min:1',
            'reward_points' => 'nullable|integer|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_active' => 'boolean',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'is_active' => $this->has('is_active'),
        ]);
    }
}
