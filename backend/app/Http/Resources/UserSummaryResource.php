<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Just enough of a user to label an audit trail.
 *
 * @mixin User
 */
#[OA\Schema(
    schema: 'UserSummary',
    title: 'User summary',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Site Administrator'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@cms.com'),
    ],
)]
class UserSummaryResource extends JsonResource
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
        ];
    }
}
