<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:120'],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->route('user')),
            ],
            // Left blank when the administrator does not want to change it.
            'password' => ['nullable', 'string', Password::min(8)],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['integer', Rule::exists('roles', 'id')],
        ];
    }
}
