<?php

namespace App\Http\Requests\Menu;

use App\Models\Menu;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMenuRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', Rule::exists('menus', 'id')],
            'title' => ['sometimes', 'string', 'max:120'],
            'slug' => [
                'sometimes',
                'string',
                'max:140',
                'alpha_dash',
                Rule::unique('menus', 'slug')->ignore($this->route('menu')),
            ],
            'position' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $menu = $this->route('menu');
                $parentId = $this->input('parent_id');

                if (! $menu instanceof Menu || $parentId === null) {
                    return;
                }

                if (in_array((int) $parentId, $menu->subtreeIds(), true)) {
                    $validator->errors()->add(
                        'parent_id',
                        'A menu item cannot be moved inside itself or one of its own children.'
                    );
                }
            },
        ];
    }
}
