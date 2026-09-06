<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');
        $categoryId = $this->route('category');

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'parent_id' => [
                'nullable',
                'integer',
                \Illuminate\Validation\Rule::exists('categories', 'id'),
            ],
        ];

        if ($isUpdate && $categoryId) {
            $rules['parent_id'][] = \Illuminate\Validation\Rule::notIn([$categoryId]);
        }

        return $rules;
    }
}
