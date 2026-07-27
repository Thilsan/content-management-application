<?php

namespace App\Http\Resources;

use App\Models\Privilege;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * @mixin Privilege
 */
#[OA\Schema(
    schema: 'Privilege',
    title: 'Privilege',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 4),
        new OA\Property(property: 'name', type: 'string', example: 'pages.delete'),
        new OA\Property(property: 'label', type: 'string', example: 'Delete pages'),
        new OA\Property(property: 'group', type: 'string', example: 'pages'),
    ],
)]
class PrivilegeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'label' => $this->label,
            'group' => $this->group,
        ];
    }
}
