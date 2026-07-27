<?php

namespace App\Http\Resources;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * @mixin Role
 */
#[OA\Schema(
    schema: 'Role',
    title: 'Role',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Administrator'),
        new OA\Property(property: 'slug', type: 'string', example: 'admin'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Full access.'),
        new OA\Property(property: 'users_count', type: 'integer', example: 2),
        new OA\Property(
            property: 'privileges',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/Privilege'),
        ),
    ],
)]
class RoleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'users_count' => $this->whenCounted('users'),
            'privileges' => PrivilegeResource::collection($this->whenLoaded('privileges')),
        ];
    }
}
