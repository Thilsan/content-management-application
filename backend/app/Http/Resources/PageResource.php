<?php

namespace App\Http\Resources;

use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Page
 */
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
            'slug' => $this->slug,
            'excerpt' => html_excerpt($this->body),
            'body' => $this->body,
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
