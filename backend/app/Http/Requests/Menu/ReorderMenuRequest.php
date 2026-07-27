<?php

namespace App\Http\Requests\Menu;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderMenuRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'distinct', Rule::exists('menus', 'id')],
            'items.*.parent_id' => ['nullable', 'integer', Rule::exists('menus', 'id')],
            'items.*.position' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->hasCycle()) {
                    $validator->errors()->add('items', 'The submitted order contains a circular reference.');
                }
            },
        ];
    }

    /**
     * Walk each item up towards the root. If a node is met twice on the way up,
     * the submitted tree loops back on itself.
     */
    private function hasCycle(): bool
    {
        $parents = [];

        foreach ($this->input('items', []) as $item) {
            $parents[(int) $item['id']] = isset($item['parent_id']) ? (int) $item['parent_id'] : null;
        }

        foreach (array_keys($parents) as $id) {
            $seen = [$id => true];
            $current = $parents[$id];

            while ($current !== null) {
                if (isset($seen[$current])) {
                    return true;
                }

                $seen[$current] = true;
                $current = $parents[$current] ?? null;
            }
        }

        return false;
    }
}
