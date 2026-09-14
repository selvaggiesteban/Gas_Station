<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReorderTopicsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['Admin', 'Content Manager']);
    }

    public function rules(): array
    {
        return [
            'items' => 'required|array',
            'items.*.id' => 'required|exists:topics,id',
            'items.*.block_id' => 'required|exists:blocks,id',
            'items.*.order' => 'required|integer',
        ];
    }
}
