<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($this->route('category'))],
            'icon' => [
                'sometimes',
                'nullable',
                Rule::when($this->hasFile('icon'), ['image', 'max:4096'], ['string', 'max:255']),
            ],
        ];
    }
}
