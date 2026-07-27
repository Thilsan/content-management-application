<?php

namespace App\Http\Resources;

use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * A page as a reader sees it: one language, already resolved, so the front end
 * never has to decide which field to show.
 *
 * @mixin Page
 */
#[OA\Schema(
    schema: 'PublicPage',
    title: 'Public page',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Who We Are'),
        new OA\Property(property: 'slug', type: 'string', example: 'who-we-are'),
        new OA\Property(property: 'excerpt', type: 'string'),
        new OA\Property(property: 'body', description: 'HTML in the resolved language.', type: 'string'),
        new OA\Property(property: 'cover_image_url', type: 'string', nullable: true),
        new OA\Property(property: 'published_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'locale', description: 'The language actually served.', type: 'string', enum: ['en', 'ar']),
        new OA\Property(property: 'direction', type: 'string', enum: ['ltr', 'rtl']),
        new OA\Property(
            property: 'is_translated',
            description: 'False when Arabic was asked for but the page fell back to English.',
            type: 'boolean',
        ),
        new OA\Property(property: 'menu', ref: '#/components/schemas/MenuSummary'),
    ],
)]
class PublicPageResource extends JsonResource
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
            'title' => $this->titleIn($locale),
            'slug' => $this->slug,
            'excerpt' => html_excerpt($this->bodyIn($locale)),
            'body' => $this->bodyIn($locale),
            'cover_image_url' => $this->coverImageUrl(),
            'published_at' => $this->published_at,
            'locale' => $locale,
            'direction' => locale_direction($locale),
            'is_translated' => $translated,
            'menu' => MenuSummaryResource::make($this->whenLoaded('menu')),
        ];
    }
}
