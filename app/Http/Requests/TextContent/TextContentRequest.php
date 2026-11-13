<?php

namespace App\Http\Requests\TextContent;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TextContentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isCreate = $this->isMethod('post');

        return [
            'title' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:255'],
            'body' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:50000'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
        ];
    }

    /**
     * Get custom messages for validation errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'The title is required.',
            'title.string' => 'The title must be a valid string.',
            'title.max' => 'The title must not exceed 255 characters.',
            'body.required' => 'The body text is required.',
            'body.string' => 'The body text must be a valid string.',
            'body.max' => 'The body text must not exceed 50,000 characters.',
            'category_id.required' => 'Please select a category.',
            'category_id.integer' => 'The category is invalid.',
            'category_id.exists' => 'The selected category does not exist.',
        ];
    }
}
