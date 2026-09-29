<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreReceiptPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['plan_id' => ['required', 'integer', 'exists:subscription_plans,id'], 'billing_cycle' => ['required', 'in:monthly,yearly'], 'receipt' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], 'reference' => ['nullable', 'string', 'max:100']];
    }
}
