<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'icon' => [
                'nullable',
                Rule::when($this->hasFile('icon'), ['image', 'max:4096'], ['string', 'max:255']),
            ],
        ];
    }
}
