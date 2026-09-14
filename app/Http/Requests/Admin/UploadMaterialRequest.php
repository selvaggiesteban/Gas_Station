<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UploadMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['Admin', 'Content Manager']);
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:51200', // 50MB
                function ($attribute, $value, $fail) {
                    $type = request()->input('type');
                    $mime = $value->getMimeType();
                    
                    if ($type === 'video' && !str_starts_with($mime, 'video/')) {
                        $fail('El archivo debe ser un video válido.');
                    }
                    if ($type === 'main_pdf' && $mime !== 'application/pdf') {
                        $fail('El archivo debe ser un PDF.');
                    }
                },
            ],
            'type' => 'required|in:main_pdf,video,resource',
            'title' => 'required_if:type,resource|nullable|string|max:255',
            'resource_type' => 'required_if:type,resource|nullable|in:summary,diagram,mind_map,extra',
            'change_notes' => 'nullable|string',
        ];
    }
}
