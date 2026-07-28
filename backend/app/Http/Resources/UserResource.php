<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * @mixin User
 */
#[OA\Schema(
    schema: 'User',
    title: 'User',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Site Administrator'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@cms.com'),
        new OA\Property(
            property: 'roles',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/Role'),
        ),
        new OA\Property(
            property: 'privileges',
            description: 'Flattened privilege names granted by every role the user holds.',
            type: 'array',
            items: new OA\Items(type: 'string'),
            example: ['pages.create', 'pages.update', 'pages.view'],
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ],
)]
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
            'privileges' => $this->privilegeNames(),
            'created_at' => $this->created_at,
        ];
    }
}
