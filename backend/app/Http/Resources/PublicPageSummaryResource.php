<?php

namespace App\Http\Resources;

use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Listing entry without the body, in the resolved language.
 *
 * @mixin Page
 */
#[OA\Schema(
    schema: 'PublicPageSummary',
    title: 'Public page summary',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'menu_id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string'),
        new OA\Property(property: 'slug', type: 'string'),
        new OA\Property(property: 'excerpt', type: 'string'),
        new OA\Property(property: 'cover_image_url', type: 'string', nullable: true),
        new OA\Property(property: 'published_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'locale', type: 'string', enum: ['en', 'ar']),
        new OA\Property(property: 'is_translated', type: 'boolean'),
    ],
)]
class PublicPageSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $requested = requested_locale();
        $translated = $this->hasTranslation($requested);

        // A partly translated page is served wholly in English rather than
        // mixing an Arabic title with an English body.
        $locale = $translated ? $requested : 'en';

        return [
            'id' => $this->id,
            'menu_id' => $this->menu_id,
            'title' => $this->titleIn($locale),
            'slug' => $this->slug,
            'excerpt' => html_excerpt($this->bodyIn($locale)),
            'cover_image_url' => $this->coverImageUrl(),
            'published_at' => $this->published_at,
            'locale' => $locale,
            'is_translated' => $translated,
        ];
    }
}
