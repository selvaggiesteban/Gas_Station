<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['Admin', 'Content Manager']);
    }

    public function rules(): array
    {
        $couponId = $this->route('coupon')?->id;
        return [
            'code' => 'required|string|max:50|unique:coupons,code,' . ($couponId ?? 'NULL'),
            'type' => 'required|in:fixed,percentage',
            'value' => 'required|numeric|min:0',
            'expires_at' => 'nullable|date',
            'max_uses' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ];
    }
}
