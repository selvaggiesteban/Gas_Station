<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendBattleChallengeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'opponent_id' => ['required', 'integer', 'min:1', 'exists:users,id'],
            'topic_id' => ['required', 'integer', 'min:1', 'exists:topics,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'opponent_id.required' => 'Debes seleccionar a un oponente.',
            'topic_id.required' => 'Debes seleccionar un tema para la batalla.',
        ];
    }
}
