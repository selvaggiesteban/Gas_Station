<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreTopicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['Admin', 'Content Manager']);
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'available_at' => 'nullable|date',
        ];
    }
}
