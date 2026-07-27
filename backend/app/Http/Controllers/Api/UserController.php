<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class UserController extends Controller
{
    #[OA\Get(
        path: '/api/users',
        summary: 'List users',
        description: 'Requires the users.view privilege, held by an administrator only.',
        security: [['sanctum' => []]],
        tags: ['Users'],
        parameters: [
            new OA\Parameter(
                name: 'search',
                description: 'Matches part of the name or the email.',
                in: 'query',
                schema: new OA\Schema(type: 'string'),
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                schema: new OA\Schema(type: 'integer', maximum: 100, minimum: 1, default: 15),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'A page of users',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/User'),
                        ),
                        new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'No or expired token'),
            new OA\Response(response: 403, description: 'Missing the users.view privilege'),
        ],
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $users = User::query()
            ->with('roles.privileges')
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $term = addcslashes(trim((string) $request->input('search')), '%_\\');

                $query->where(function (Builder $query) use ($term): void {
                    $query->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
            })
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return UserResource::collection($users);
    }

    #[OA\Post(
        path: '/api/users',
        summary: 'Create a user',
        description: 'Requires the users.create privilege. The password is hashed by the model.',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Amara Perera'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'amara@cms.test'),
                    new OA\Property(
                        property: 'password',
                        description: 'At least eight characters.',
                        type: 'string',
                        format: 'password',
                    ),
                    new OA\Property(
                        property: 'roles',
                        description: 'Role ids to attach.',
                        type: 'array',
                        items: new OA\Items(type: 'integer'),
                        example: [2],
                    ),
                ],
            ),
        ),
        tags: ['Users'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Created',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/User')],
                ),
            ),
            new OA\Response(response: 401, description: 'No or expired token'),
            new OA\Response(response: 403, description: 'Missing the users.create privilege'),
            new OA\Response(
                response: 422,
                description: 'Validation failed',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
            ),
        ],
    )]
    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create(Arr::only($data, ['name', 'email', 'password']));
        $user->roles()->sync($data['roles'] ?? []);

        return UserResource::make($user->load('roles.privileges'))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/users/{user}',
        summary: 'Read one user',
        security: [['sanctum' => []]],
        tags: ['Users'],
        parameters: [
            new OA\Parameter(
                name: 'user',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'The user',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/User')],
                ),
            ),
            new OA\Response(response: 401, description: 'No or expired token'),
            new OA\Response(response: 403, description: 'Missing the users.view privilege'),
            new OA\Response(response: 404, description: 'No such user'),
        ],
    )]
    public function show(User $user): UserResource
    {
        return UserResource::make($user->load('roles.privileges'));
    }

    #[OA\Put(
        path: '/api/users/{user}',
        summary: 'Update a user',
        description: 'Leave the password out, or send null, to keep the current one. Sending roles '.
            'replaces the whole set. Requires the users.update privilege.',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Amara Perera'),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', nullable: true),
                    new OA\Property(
                        property: 'roles',
                        type: 'array',
                        items: new OA\Items(type: 'integer'),
                        example: [1],
                    ),
                ],
            ),
        ),
        tags: ['Users'],
        parameters: [
            new OA\Parameter(
                name: 'user',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Updated',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/User')],
                ),
            ),
            new OA\Response(response: 401, description: 'No or expired token'),
            new OA\Response(response: 403, description: 'Missing the users.update privilege'),
            new OA\Response(response: 404, description: 'No such user'),
            new OA\Response(
                response: 422,
                description: 'Validation failed',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
            ),
        ],
    )]
    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $data = $request->validated();

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update(Arr::only($data, ['name', 'email', 'password']));

        if (array_key_exists('roles', $data)) {
            $user->roles()->sync($data['roles']);
        }

        return UserResource::make($user->load('roles.privileges'));
    }

    #[OA\Delete(
        path: '/api/users/{user}',
        summary: 'Delete a user',
        description: 'An administrator cannot delete the account they are signed in with. '.
            'Requires the users.delete privilege.',
        security: [['sanctum' => []]],
        tags: ['Users'],
        parameters: [
            new OA\Parameter(
                name: 'user',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 2),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Deleted',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse'),
            ),
            new OA\Response(response: 401, description: 'No or expired token'),
            new OA\Response(response: 403, description: 'Missing the users.delete privilege'),
            new OA\Response(response: 404, description: 'No such user'),
            new OA\Response(
                response: 422,
                description: 'Refused, because it is the signed in account',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
            ),
        ],
    )]
    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->is($request->user())) {
            throw ValidationException::withMessages([
                'user' => 'You cannot delete the account you are signed in with.',
            ]);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted.']);
    }
}
