<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\ReorderMenuRequest;
use App\Http\Requests\Menu\StoreMenuRequest;
use App\Http\Requests\Menu\UpdateMenuRequest;
use App\Http\Resources\MenuResource;
use App\Models\Menu;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class MenuController extends Controller
{
    #[OA\Get(
        path: '/api/menus',
        summary: 'The whole menu tree, active or not',
        description: 'Nested to any depth and ordered by position, with a page count on each node. '.
            'Requires the menus.view privilege, which a moderator does hold so that pages can be '.
            'filed under a menu item.',
        security: [['sanctum' => []]],
        tags: ['Menus'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'The tree',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Menu'),
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'No or expired token'),
            new OA\Response(response: 403, description: 'Missing the menus.view privilege'),
        ],
    )]
    public function index(): AnonymousResourceCollection
    {
        return MenuResource::collection(
            Menu::tree(Menu::query()->withCount('pages'))
        );
    }

    #[OA\Post(
        path: '/api/menus',
        summary: 'Add a menu item',
        description: 'Lands at the end of its level unless a position is given. '.
            'Requires the menus.create privilege.',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Contact'),
                    new OA\Property(
                        property: 'parent_id',
                        description: 'Omit or send null for a top level item.',
                        type: 'integer',
                        nullable: true,
                        example: null,
                    ),
                    new OA\Property(property: 'slug', type: 'string', example: 'contact'),
                    new OA\Property(property: 'position', type: 'integer', example: 3),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                ],
            ),
        ),
        tags: ['Menus'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Created',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Menu')],
                ),
            ),
            new OA\Response(response: 401, description: 'No or expired token'),
            new OA\Response(response: 403, description: 'Missing the menus.create privilege'),
            new OA\Response(
                response: 422,
                description: 'Validation failed',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
            ),
        ],
    )]
    public function store(StoreMenuRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['slug'] ??= unique_slug($data['title'], 'menus');
        $data['position'] ??= Menu::where('parent_id', $data['parent_id'] ?? null)->count();

        $menu = Menu::create($data);

        return MenuResource::make($menu->loadCount('pages'))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Put(
        path: '/api/menus/{menu}',
        summary: 'Rename, move or switch off a menu item',
        description: 'Switching a parent off hides its whole branch from the public site. '.
            'An item cannot be moved inside itself or one of its own children. '.
            'Requires the menus.update privilege.',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'About Us'),
                    new OA\Property(property: 'parent_id', type: 'integer', nullable: true),
                    new OA\Property(property: 'slug', type: 'string'),
                    new OA\Property(property: 'position', type: 'integer'),
                    new OA\Property(property: 'is_active', type: 'boolean'),
                ],
            ),
        ),
        tags: ['Menus'],
        parameters: [
            new OA\Parameter(
                name: 'menu',
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
                    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Menu')],
                ),
            ),
            new OA\Response(response: 401, description: 'No or expired token'),
            new OA\Response(response: 403, description: 'Missing the menus.update privilege'),
            new OA\Response(response: 404, description: 'No such menu item'),
            new OA\Response(
                response: 422,
                description: 'Validation failed, including an attempt to nest an item inside itself',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
            ),
        ],
    )]
    public function update(UpdateMenuRequest $request, Menu $menu): MenuResource
    {
        $menu->update($request->validated());

        return MenuResource::make($menu->loadCount('pages'));
    }

    /**
     * Deleting a branch cascades to its children, so refuse while any page in the
     * branch still points at it rather than letting the database reject it.
     */
    #[OA\Delete(
        path: '/api/menus/{menu}',
        summary: 'Delete a menu item and its children',
        description: 'Refused with a 422 while any page in the branch is still filed under it, '.
            'including pages sitting in the trash. Requires the menus.delete privilege.',
        security: [['sanctum' => []]],
        tags: ['Menus'],
        parameters: [
            new OA\Parameter(
                name: 'menu',
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
            new OA\Response(response: 403, description: 'Missing the menus.delete privilege'),
            new OA\Response(response: 404, description: 'No such menu item'),
            new OA\Response(
                response: 422,
                description: 'The branch still holds pages',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
            ),
        ],
    )]
    public function destroy(Menu $menu): JsonResponse
    {
        $branch = $menu->subtreeIds();

        if (Page::withTrashed()->whereIn('menu_id', $branch)->exists()) {
            throw ValidationException::withMessages([
                'menu' => 'Move or delete the pages under this menu item first.',
            ]);
        }

        $menu->delete();

        return response()->json(['message' => 'Menu item deleted.']);
    }

    /**
     * Persist a whole tree in one go. The client sends every node with its new
     * parent and position, which is what the drag and drop editor produces.
     */
    #[OA\Post(
        path: '/api/menus/reorder',
        summary: 'Save a new menu order',
        description: 'Send every node in the tree with its new parent and position. The payload is '.
            'rejected if it describes a loop. Returns the freshly built tree. '.
            'Requires the menus.reorder privilege.',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['items'],
                properties: [
                    new OA\Property(
                        property: 'items',
                        type: 'array',
                        items: new OA\Items(
                            required: ['id', 'position'],
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 3),
                                new OA\Property(property: 'parent_id', type: 'integer', nullable: true, example: 1),
                                new OA\Property(property: 'position', type: 'integer', example: 0),
                            ],
                            type: 'object',
                        ),
                    ),
                ],
            ),
        ),
        tags: ['Menus'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'The reordered tree',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Menu'),
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'No or expired token'),
            new OA\Response(response: 403, description: 'Missing the menus.reorder privilege'),
            new OA\Response(
                response: 422,
                description: 'Unknown ids, or an order that loops back on itself',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
            ),
        ],
    )]
    public function reorder(ReorderMenuRequest $request): AnonymousResourceCollection
    {
        DB::transaction(function () use ($request): void {
            foreach ($request->validated()['items'] as $item) {
                Menu::whereKey($item['id'])->update([
                    'parent_id' => $item['parent_id'] ?? null,
                    'position' => $item['position'],
                ]);
            }
        });

        return $this->index();
    }
}
