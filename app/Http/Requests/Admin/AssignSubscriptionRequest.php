<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AssignSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['Admin', 'Content Manager']);
    }

    public function rules(): array
    {
        return [
            'plan_id' => 'required|exists:plans,id',
            'duration_months' => 'required|integer|min:1',
            'payment_method' => 'required|string|in:transfer,manual,cash',
        ];
    }
}
