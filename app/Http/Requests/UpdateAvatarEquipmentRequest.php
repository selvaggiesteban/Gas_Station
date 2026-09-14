<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAvatarEquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item_id' => 'required|exists:avatar_items,id',
            'action' => 'required|in:equip,unequip',
        ];
    }
}
