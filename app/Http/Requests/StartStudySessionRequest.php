<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartStudySessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'schedule_id' => 'nullable|exists:schedules,id',
            'topic_id' => 'nullable|exists:topics,id',
            'type' => 'required|in:pomodoro,short_break,long_break',
            'duration_minutes' => 'nullable|integer|min:1|max:120',
        ];
    }
}
