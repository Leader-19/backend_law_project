<?php

namespace App\Http\Requests\Doc;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class DocumentRequest extends FormRequest
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

            'doc_name' => 'required|string|max:255',
            'doc_title' => 'required|string|max:255',
            'doc_upload' => [$isCreate ? 'required' : 'nullable', 'file', 'max:6291456'],
            'image' => 'nullable|image|max:5000',
            'description' => 'nullable|string|max:500',
            'category_id' => 'required|exists:categories,id',

        ];
    }

    /**
     * PHP can silently drop an upload that exceeds upload_max_filesize /
     * post_max_size before Laravel validation runs, producing a confusing
     * "required" or "must be a file" error. Detect that and report it clearly.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            foreach (['doc_upload', 'image'] as $field) {
                $file = $this->file($field);

                if ($file && $file->getError() !== UPLOAD_ERR_OK) {
                    $limit = ini_get('upload_max_filesize');
                    $v->errors()->add(
                        $field,
                        "The uploaded file is larger than the server's maximum allowed size ({$limit}). Please upload a smaller file or contact the administrator."
                    );
                }
            }

            // post_max_size exceeded: the whole request body is dropped, so the
            // file appears missing even though one was sent.
            if ($this->isMethod('post') && empty($_FILES)
                && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > $this->phpIniBytes(ini_get('post_max_size'))) {
                $v->errors()->add(
                    'doc_upload',
                    'The uploaded file exceeds the server\'s maximum allowed size. Please upload a smaller file or contact the administrator.'
                );
            }
        });
    }

    private function phpIniBytes(string $value): int
    {
        $unit = strtolower(substr($value, -1));
        $size = (int) $value;

        return match ($unit) {
            'g' => $size * 1024 * 1024 * 1024,
            'm' => $size * 1024 * 1024,
            'k' => $size * 1024,
            default => $size,
        };
    }
}
