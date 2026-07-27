<?php

namespace App\Http\Resources;

use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * @mixin Page
 */
#[OA\Schema(
    schema: 'Page',
    title: 'Page',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'menu_id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Who We Are'),
        new OA\Property(property: 'title_ar', type: 'string', nullable: true, example: 'من نحن'),
        new OA\Property(property: 'slug', type: 'string', example: 'who-we-are'),
        new OA\Property(property: 'excerpt', type: 'string', example: 'A short history. We started in 2014...'),
        new OA\Property(
            property: 'body',
            description: 'HTML produced by CKEditor.',
            type: 'string',
            example: '<h2>A short history</h2><p>We started in 2014.</p>',
        ),
        new OA\Property(
            property: 'cover_image_url',
            type: 'string',
            nullable: true,
            example: 'http://localhost:8000/storage/covers/8fJ2.jpg',
        ),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'published'], example: 'published'),
        new OA\Property(property: 'published_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'position', type: 'integer', example: 0),
        new OA\Property(
            property: 'is_visible',
            description: 'True when the page is published and its publish date has passed.',
            type: 'boolean',
            example: true,
        ),
        new OA\Property(property: 'menu', ref: '#/components/schemas/MenuSummary'),
        new OA\Property(property: 'created_by', ref: '#/components/schemas/UserSummary'),
        new OA\Property(property: 'updated_by', ref: '#/components/schemas/UserSummary'),
        new OA\Property(property: 'deleted_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
)]
class PageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'menu_id' => $this->menu_id,
            'title' => $this->title,
            'title_ar' => $this->title_ar,
            'slug' => $this->slug,
            'excerpt' => html_excerpt($this->body),
            'body' => $this->body,
            'body_ar' => $this->body_ar,
            'is_translated' => $this->hasTranslation('ar'),
            'cover_image_url' => $this->coverImageUrl(),
            'status' => $this->status->value,
            'published_at' => $this->published_at,
            'position' => $this->position,
            'is_visible' => $this->isVisible(),
            'menu' => MenuSummaryResource::make($this->whenLoaded('menu')),
            'created_by' => UserSummaryResource::make($this->whenLoaded('creator')),
            'updated_by' => UserSummaryResource::make($this->whenLoaded('editor')),
            'deleted_at' => $this->deleted_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
