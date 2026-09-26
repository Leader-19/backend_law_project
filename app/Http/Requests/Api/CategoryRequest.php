<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
                Rule::exists('categories', 'id'),
            ],
        ];

        if ($isUpdate && $categoryId) {
            $rules['parent_id'][] = Rule::notIn([$categoryId]);
        }

        return $rules;
    }
}
