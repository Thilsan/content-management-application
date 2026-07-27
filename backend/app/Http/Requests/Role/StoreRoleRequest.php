<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', 'alpha_dash', Rule::unique('roles', 'slug')],
            'description' => ['nullable', 'string', 'max:255'],
            'privileges' => ['sometimes', 'array'],
            'privileges.*' => ['integer', Rule::exists('privileges', 'id')],
        ];
    }
}
