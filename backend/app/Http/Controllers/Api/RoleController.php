<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class RoleController extends Controller
{
    #[OA\Get(
        path: '/api/roles',
        summary: 'List roles with the privileges they grant',
        description: 'Not paginated; the list is short by nature. Requires the roles.view privilege.',
        security: [['sanctum' => []]],
        tags: ['Roles'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'The roles',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Role'),
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'No or expired token'),
            new OA\Response(response: 403, description: 'Missing the roles.view privilege'),
        ],
    )]
    public function index(): AnonymousResourceCollection
    {
        $roles = Role::query()
            ->with('privileges')
            ->withCount('users')
            ->orderBy('name')
            ->get();

        return RoleResource::collection($roles);
    }

    #[OA\Post(
        path: '/api/roles',
        summary: 'Create a role',
        description: 'Requires the roles.create privilege.',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Copy Editor'),
                    new OA\Property(property: 'slug', type: 'string', example: 'copy-editor'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(
                        property: 'privileges',
                        description: 'Privilege ids to grant.',
                        type: 'array',
                        items: new OA\Items(type: 'integer'),
                        example: [1, 3],
                    ),
                ],
            ),
        ),
        tags: ['Roles'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Created',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Role')],
                ),
            ),
            new OA\Response(response: 401, description: 'No or expired token'),
            new OA\Response(response: 403, description: 'Missing the roles.create privilege'),
            new OA\Response(
                response: 422,
                description: 'Validation failed',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
            ),
        ],
    )]
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['slug'] ??= unique_slug($data['name'], 'roles');

        $role = Role::create(Arr::only($data, ['name', 'slug', 'description']));
        $role->privileges()->sync($data['privileges'] ?? []);

        return RoleResource::make($role->load('privileges')->loadCount('users'))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/roles/{role}',
        summary: 'Read one role',
        security: [['sanctum' => []]],
        tags: ['Roles'],
        parameters: [
            new OA\Parameter(
                name: 'role',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'The role',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Role')],
                ),
            ),
            new OA\Response(response: 401, description: 'No or expired token'),
            new OA\Response(response: 403, description: 'Missing the roles.view privilege'),
            new OA\Response(response: 404, description: 'No such role'),
        ],
    )]
    public function show(Role $role): RoleResource
    {
        return RoleResource::make($role->load('privileges')->loadCount('users'));
    }

    #[OA\Put(
        path: '/api/roles/{role}',
        summary: 'Update a role and what it may do',
        description: 'Sending privileges replaces the whole set, which takes effect on the next '.
            'request made by anyone holding the role. Requires the roles.update privilege.',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'slug', type: 'string'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(
                        property: 'privileges',
                        type: 'array',
                        items: new OA\Items(type: 'integer'),
                        example: [1, 2, 3],
                    ),
                ],
            ),
        ),
        tags: ['Roles'],
        parameters: [
            new OA\Parameter(
                name: 'role',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 2),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Updated',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Role')],
                ),
            ),
            new OA\Response(response: 401, description: 'No or expired token'),
            new OA\Response(response: 403, description: 'Missing the roles.update privilege'),
            new OA\Response(response: 404, description: 'No such role'),
            new OA\Response(
                response: 422,
                description: 'Validation failed',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
            ),
        ],
    )]
    public function update(UpdateRoleRequest $request, Role $role): RoleResource
    {
        $data = $request->validated();

        $role->update(Arr::only($data, ['name', 'slug', 'description']));

        if (array_key_exists('privileges', $data)) {
            $role->privileges()->sync($data['privileges']);
        }

        return RoleResource::make($role->load('privileges')->loadCount('users'));
    }

    #[OA\Delete(
        path: '/api/roles/{role}',
        summary: 'Delete a role',
        description: 'Refused with a 422 while any user still holds it. Requires the roles.delete privilege.',
        security: [['sanctum' => []]],
        tags: ['Roles'],
        parameters: [
            new OA\Parameter(
                name: 'role',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 3),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Deleted',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse'),
            ),
            new OA\Response(response: 401, description: 'No or expired token'),
            new OA\Response(response: 403, description: 'Missing the roles.delete privilege'),
            new OA\Response(response: 404, description: 'No such role'),
            new OA\Response(
                response: 422,
                description: 'Users still hold this role',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
            ),
        ],
    )]
    public function destroy(Role $role): JsonResponse
    {
        if ($role->users()->exists()) {
            throw ValidationException::withMessages([
                'role' => 'Reassign the users holding this role before deleting it.',
            ]);
        }

        $role->delete();

        return response()->json(['message' => 'Role deleted.']);
    }
}
