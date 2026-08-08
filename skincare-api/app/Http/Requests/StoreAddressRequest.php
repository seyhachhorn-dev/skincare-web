<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'province' => ['required', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:255'],
            'commune' => ['required', 'string', 'max:255'],
            'house_no' => ['required', 'string', 'max:255'],
            'pickup_point' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(['home', 'office', 'school', 'other'])],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
