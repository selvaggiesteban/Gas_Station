<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitBattleScoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $battle = $this->route('battle');
        return $battle && ($battle->challenger_id === $this->user()->id || $battle->opponent_id === $this->user()->id);
    }

    public function rules(): array
    {
        return [
            'score' => 'required|integer|min:0|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'score.required' => 'La puntuación es obligatoria.',
            'score.integer' => 'La puntuación debe ser un número entero.',
            'score.min' => 'La puntuación mínima es 0.',
            'score.max' => 'La puntuación máxima es 100.',
        ];
    }
}
