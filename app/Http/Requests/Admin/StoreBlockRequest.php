<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreBlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['Admin', 'Content Manager']);
    }

    public function rules(): array
    {
        return [
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'order' => 'nullable|integer|min:0',
        ];
    }
}
