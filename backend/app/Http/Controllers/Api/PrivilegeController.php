<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Privilege\StorePrivilegeRequest;
use App\Http\Requests\Privilege\UpdatePrivilegeRequest;
use App\Http\Resources\PrivilegeResource;
use App\Models\Privilege;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

class PrivilegeController extends Controller
{
    #[OA\Get(
        path: '/api/privileges',
        summary: 'The privilege catalogue',
        description: 'Every row here is also a gate ability the API can check. Requires the '.
            'privileges.view privilege.',
        security: [['sanctum' => []]],
        tags: ['Privileges'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'The catalogue, ordered by group',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Privilege'),
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'No or expired token'),
            new OA\Response(response: 403, description: 'Missing the privileges.view privilege'),
        ],
    )]
    public function index(): AnonymousResourceCollection
    {
        $privileges = Privilege::query()
            ->orderBy('group')
            ->orderBy('name')
            ->get();

        return PrivilegeResource::collection($privileges);
    }

    #[OA\Post(
        path: '/api/privileges',
        summary: 'Add a privilege',
        description: 'The name must read as group.action, for example reports.export. A new privilege '.
            'becomes checkable straight away, so granting it to a role is all that is needed to open '.
            'up a guarded endpoint. Requires the privileges.create privilege.',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'label', 'group'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'reports.export'),
                    new OA\Property(property: 'label', type: 'string', example: 'Export reports'),
                    new OA\Property(property: 'group', type: 'string', example: 'reports'),
                ],
            ),
        ),
        tags: ['Privileges'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Created',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Privilege')],
                ),
            ),
            new OA\Response(response: 401, description: 'No or expired token'),
            new OA\Response(response: 403, description: 'Missing the privileges.create privilege'),
            new OA\Response(
                response: 422,
                description: 'Validation failed, including a name that is not group.action',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
            ),
        ],
    )]
    public function store(StorePrivilegeRequest $request): JsonResponse
    {
        $privilege = Privilege::create($request->validated());

        return PrivilegeResource::make($privilege)
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Put(
        path: '/api/privileges/{privilege}',
        summary: 'Update a privilege',
        description: 'Renaming a privilege changes the ability the API checks for, so the endpoints '.
            'guarded by the old name stop matching. Requires the privileges.update privilege.',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'reports.export'),
                    new OA\Property(property: 'label', type: 'string', example: 'Export reports'),
                    new OA\Property(property: 'group', type: 'string', example: 'reports'),
                ],
            ),
        ),
        tags: ['Privileges'],
        parameters: [
            new OA\Parameter(
                name: 'privilege',
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
                    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Privilege')],
                ),
            ),
            new OA\Response(response: 401, description: 'No or expired token'),
            new OA\Response(response: 403, description: 'Missing the privileges.update privilege'),
            new OA\Response(response: 404, description: 'No such privilege'),
            new OA\Response(
                response: 422,
                description: 'Validation failed',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
            ),
        ],
    )]
    public function update(UpdatePrivilegeRequest $request, Privilege $privilege): PrivilegeResource
    {
        $privilege->update($request->validated());

        return PrivilegeResource::make($privilege);
    }

    #[OA\Delete(
        path: '/api/privileges/{privilege}',
        summary: 'Delete a privilege',
        description: 'Detaches it from every role on the way out, which removes that ability from '.
            'anyone who held it. Requires the privileges.delete privilege.',
        security: [['sanctum' => []]],
        tags: ['Privileges'],
        parameters: [
            new OA\Parameter(
                name: 'privilege',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Deleted',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse'),
            ),
            new OA\Response(response: 401, description: 'No or expired token'),
            new OA\Response(response: 403, description: 'Missing the privileges.delete privilege'),
            new OA\Response(response: 404, description: 'No such privilege'),
        ],
    )]
    public function destroy(Privilege $privilege): JsonResponse
    {
        $privilege->delete();

        return response()->json(['message' => 'Privilege deleted.']);
    }
}
