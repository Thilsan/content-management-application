<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:120'],
            'slug' => [
                'sometimes',
                'string',
                'max:140',
                'alpha_dash',
                Rule::unique('roles', 'slug')->ignore($this->route('role')),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'privileges' => ['sometimes', 'array'],
            'privileges.*' => ['integer', Rule::exists('privileges', 'id')],
        ];
    }
}
