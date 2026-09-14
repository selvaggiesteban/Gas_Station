<?php

namespace App\Http\Requests\ContentManager;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['Admin', 'Content Manager']);
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:info,warning,urgent',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'attachment' => 'nullable|file|max:10240', // 10 MB
            'target_cuerpos' => 'nullable|array',
            'target_cuerpos.*' => 'exists:cuerpos,id',
        ];
    }
}
