<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->level >= 5;
    }

    public function rules(): array
    {
        return [
            'avatar' => 'required|image|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'avatar.required' => 'Debes seleccionar una imagen.',
            'avatar.image' => 'El archivo debe ser una imagen.',
            'avatar.max' => 'La imagen no puede pesar más de 2MB.',
        ];
    }
}
