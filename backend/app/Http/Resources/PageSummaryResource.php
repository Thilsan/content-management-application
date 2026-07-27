<?php

namespace App\Http\Resources;

use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Page without its body, for menus and listings.
 *
 * @mixin Page
 */
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
