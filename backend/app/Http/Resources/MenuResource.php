<?php

namespace App\Http\Resources;

use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Menu
 */
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
            'title' => $this->title,
            'slug' => $this->slug,
            'position' => $this->position,
            'is_active' => $this->is_active,
            'pages_count' => $this->whenCounted('pages'),
            'pages' => PageSummaryResource::collection($this->whenLoaded('pages')),
            'children' => self::collection($this->whenLoaded('children')),
        ];
    }
}
