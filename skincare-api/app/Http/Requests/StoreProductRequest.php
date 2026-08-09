<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Admin check is enforced by the 'admin' route middleware.
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'integer', 'min:0'],
            'image' => ['required', 'image', 'max:4096'],
            'brand' => ['nullable', 'string', 'max:255'],
            'size' => ['nullable', 'string', 'max:255'],
        ];
    }
}
