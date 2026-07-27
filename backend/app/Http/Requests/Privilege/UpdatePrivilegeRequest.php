<?php

namespace App\Http\Requests\Privilege;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePrivilegeRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'string',
                'max:120',
                'regex:/^[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*$/',
                Rule::unique('privileges', 'name')->ignore($this->route('privilege')),
            ],
            'label' => ['sometimes', 'string', 'max:160'],
            'group' => ['sometimes', 'string', 'max:60'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.regex' => 'A privilege name looks like group.action, for example pages.delete.',
        ];
    }
}
