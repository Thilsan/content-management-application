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
    schema: 'MenuSummary',
    title: 'Menu summary',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 3),
        new OA\Property(property: 'title', description: 'Resolved for the requested language.', type: 'string', example: 'Careers'),
        new OA\Property(property: 'slug', type: 'string', example: 'careers'),
    ],
)]
class MenuSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->titleIn(requested_locale()),
            'slug' => $this->slug,
        ];
    }
}
