<?php

namespace App\Http\Requests\Privilege;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePrivilegeRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:120',
                'regex:/^[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*$/',
                Rule::unique('privileges', 'name'),
            ],
            'label' => ['required', 'string', 'max:160'],
            'group' => ['required', 'string', 'max:60'],
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
