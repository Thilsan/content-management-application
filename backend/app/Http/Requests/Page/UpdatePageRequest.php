<?php

namespace App\Http\Requests\Page;

use App\Enums\PageStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePageRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'menu_id' => ['sometimes', 'integer', Rule::exists('menus', 'id')],
            'title' => ['sometimes', 'string', 'max:200'],
            'title_ar' => ['nullable', 'string', 'max:200'],
            'slug' => [
                'sometimes',
                'string',
                'max:220',
                'alpha_dash',
                Rule::unique('pages', 'slug')->ignore($this->route('page')),
            ],
            'body' => ['sometimes', 'string'],
            'body_ar' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_cover' => ['sometimes', 'boolean'],
            'status' => ['sometimes', Rule::enum(PageStatus::class)],
            'published_at' => ['nullable', 'date'],
            'position' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cover_image.max' => 'The cover image may not be larger than 4 MB.',
        ];
    }
}
