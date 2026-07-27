<?php

namespace App\Http\Resources;

use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Page without its body, for menus and listings.
 *
 * @mixin Page
 */
#[OA\Schema(
    schema: 'PageSummary',
    title: 'Page summary',
    description: 'Page without its body, used by the menu tree and the public listing.',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'menu_id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Who We Are'),
        new OA\Property(property: 'slug', type: 'string', example: 'who-we-are'),
        new OA\Property(property: 'excerpt', type: 'string', example: 'A short history. We started in 2014...'),
        new OA\Property(property: 'cover_image_url', type: 'string', nullable: true),
        new OA\Property(property: 'published_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
class PageSummaryResource extends JsonResource
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
            'slug' => $this->slug,
            'excerpt' => html_excerpt($this->body),
            'cover_image_url' => $this->coverImageUrl(),
            'published_at' => $this->published_at,
        ];
    }
}
