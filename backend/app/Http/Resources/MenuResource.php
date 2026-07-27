<?php

namespace App\Http\Resources;

use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * @mixin Menu
 */
#[OA\Schema(
    schema: 'Menu',
    title: 'Menu item',
    description: 'A node of the menu tree. Children nest to any depth.',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'parent_id', type: 'integer', nullable: true, example: null),
        new OA\Property(property: 'title', description: 'Resolved for the requested language.', type: 'string', example: 'About'),
        new OA\Property(property: 'title_ar', type: 'string', nullable: true, example: 'من نحن'),
        new OA\Property(property: 'slug', type: 'string', example: 'about'),
        new OA\Property(property: 'position', type: 'integer', example: 0),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(
            property: 'pages_count',
            description: 'Present on the back end tree only.',
            type: 'integer',
            example: 2,
        ),
        new OA\Property(
            property: 'pages',
            description: 'Present on the public tree only, already filtered to live pages.',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/PublicPageSummary'),
        ),
        new OA\Property(
            property: 'children',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/Menu'),
        ),
    ],
)]
class MenuResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'title' => $this->titleIn(requested_locale()),
            'title_ar' => $this->title_ar,
            'slug' => $this->slug,
            'position' => $this->position,
            'is_active' => $this->is_active,
            'pages_count' => $this->whenCounted('pages'),
            'pages' => PublicPageSummaryResource::collection($this->whenLoaded('pages')),
            'children' => self::collection($this->whenLoaded('children')),
        ];
    }
}
