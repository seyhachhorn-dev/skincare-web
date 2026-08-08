<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Ownership is enforced separately via AddressPolicy in the controller.
        return true;
    }

    public function rules(): array
    {
        return [
            'province' => ['sometimes', 'string', 'max:255'],
            'district' => ['sometimes', 'string', 'max:255'],
            'commune' => ['sometimes', 'string', 'max:255'],
            'house_no' => ['sometimes', 'string', 'max:255'],
            'pickup_point' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'type' => ['sometimes', Rule::in(['home', 'office', 'school', 'other'])],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
