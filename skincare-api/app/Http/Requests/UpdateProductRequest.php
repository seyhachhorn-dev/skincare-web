<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'price' => ['sometimes', 'integer', 'min:0'],
            'image' => ['sometimes', 'image', 'max:4096'],
            'brand' => ['sometimes', 'nullable', 'string', 'max:255'],
            'size' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
