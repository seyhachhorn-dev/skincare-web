<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Admin check is enforced by the 'admin' route middleware.
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => ['required', Rule::in(['admin', 'user'])],
        ];
    }
}
