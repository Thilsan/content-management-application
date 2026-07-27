<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

/**
 * Response fragments that repeat across the API. This class exists only to carry
 * the OpenAPI component definitions; nothing instantiates it.
 */
#[OA\Schema(
    schema: 'PaginationLinks',
    title: 'Pagination links',
    properties: [
        new OA\Property(property: 'first', type: 'string', nullable: true),
        new OA\Property(property: 'last', type: 'string', nullable: true),
        new OA\Property(property: 'prev', type: 'string', nullable: true),
        new OA\Property(property: 'next', type: 'string', nullable: true),
    ],
)]
#[OA\Schema(
    schema: 'PaginationMeta',
    title: 'Pagination meta',
    properties: [
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'from', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'last_page', type: 'integer', example: 3),
        new OA\Property(property: 'path', type: 'string', example: 'http://localhost:8000/api/pages'),
        new OA\Property(property: 'per_page', type: 'integer', example: 15),
        new OA\Property(property: 'to', type: 'integer', nullable: true, example: 15),
        new OA\Property(property: 'total', type: 'integer', example: 42),
    ],
)]
#[OA\Schema(
    schema: 'ValidationError',
    title: 'Validation error',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'The title field is required.'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            example: ['title' => ['The title field is required.']],
            additionalProperties: new OA\AdditionalProperties(
                type: 'array',
                items: new OA\Items(type: 'string'),
            ),
        ),
    ],
)]
#[OA\Schema(
    schema: 'MessageResponse',
    title: 'Message',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Page moved to trash.'),
    ],
)]
final class Components
{
    //
}
