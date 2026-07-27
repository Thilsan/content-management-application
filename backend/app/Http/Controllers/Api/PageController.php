<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Page\IndexPageRequest;
use App\Http\Requests\Page\StorePageRequest;
use App\Http\Requests\Page\UpdatePageRequest;
use App\Http\Resources\PageResource;
use App\Models\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;

class PageController extends Controller
{
    private const COVER_DIRECTORY = 'covers';

    private const RELATIONS = ['menu', 'creator', 'editor'];

    #[OA\Get(
        path: '/api/pages',
        summary: 'List pages with search, filters and pagination',
        description: 'Includes drafts and scheduled pages, unlike the public endpoints. '.
            'Requires the pages.view privilege.',
        security: [['sanctum' => []]],
        tags: ['Pages'],
        parameters: [
            new OA\Parameter(
                name: 'search',
                description: 'Matches part of the title.',
                in: 'query',
                schema: new OA\Schema(type: 'string', example: 'report'),
            ),
            new OA\Parameter(
                name: 'menu_id',
                description: 'Only pages filed under this menu item.',
                in: 'query',
                schema: new OA\Schema(type: 'integer', example: 2),
            ),
            new OA\Parameter(
                name: 'status',
                in: 'query',
                schema: new OA\Schema(type: 'string', enum: ['draft', 'published']),
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                schema: new OA\Schema(type: 'integer', maximum: 100, minimum: 1, default: 15),
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                schema: new OA\Schema(type: 'integer', minimum: 1, default: 1),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'A page of results',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Page'),
                        ),
                        new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'No or expired token'),
            new OA\Response(response: 403, description: 'Missing the pages.view privilege'),
            new OA\Response(
                response: 422,
                description: 'A filter was not understood',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
            ),
        ],
    )]
    public function index(IndexPageRequest $request): AnonymousResourceCollection
    {
        $pages = $this->applyFilters(Page::query(), $request)
            ->with(self::RELATIONS)
            ->latest('id')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return PageResource::collection($pages);
    }

    #[OA\Get(
        path: '/api/pages/trashed',
        summary: 'List soft deleted pages',
        description: 'The trash. Requires the pages.restore privilege, which a moderator does not hold.',
        security: [['sanctum' => []]],
        tags: ['Pages'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'menu_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'A page of trashed results',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Page'),
                        ),
                        new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'No or expired token'),
            new OA\Response(response: 403, description: 'Missing the pages.restore privilege'),
        ],
    )]
    public function trashed(IndexPageRequest $request): AnonymousResourceCollection
    {
        $pages = $this->applyFilters(Page::onlyTrashed(), $request)
            ->with(self::RELATIONS)
            ->orderByDesc('deleted_at')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return PageResource::collection($pages);
    }

    #[OA\Post(
        path: '/api/pages',
        summary: 'Create a page',
        description: 'Send as multipart/form-data when a cover image is attached. '.
            'The slug is derived from the title unless one is supplied. '.
            'Requires the pages.create privilege.',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['menu_id', 'title', 'body', 'status'],
                    properties: [
                        new OA\Property(property: 'menu_id', type: 'integer', example: 1),
                        new OA\Property(property: 'title', type: 'string', example: 'Annual Report 2026'),
                        new OA\Property(property: 'slug', type: 'string', example: 'annual-report-2026'),
                        new OA\Property(
                            property: 'body',
                            description: 'HTML from CKEditor.',
                            type: 'string',
                            example: '<h2>The year in review</h2>',
                        ),
                        new OA\Property(
                            property: 'cover_image',
                            description: 'jpg, jpeg, png or webp, up to 4 MB.',
                            type: 'string',
                            format: 'binary',
                        ),
                        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'published']),
                        new OA\Property(
                            property: 'published_at',
                            description: 'Leave empty to publish immediately, or set a future date to schedule.',
                            type: 'string',
                            format: 'date-time',
                        ),
                        new OA\Property(property: 'position', type: 'integer', example: 0),
                    ],
                ),
            ),
        ),
        tags: ['Pages'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Created',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Page')],
                ),
            ),
            new OA\Response(response: 401, description: 'No or expired token'),
            new OA\Response(response: 403, description: 'Missing the pages.create privilege'),
            new OA\Response(
                response: 422,
                description: 'Validation failed',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
            ),
        ],
    )]
    public function store(StorePageRequest $request): JsonResponse
    {
        $data = $request->validated();
        unset($data['cover_image']);

        $data['slug'] ??= unique_slug($data['title'], 'pages');

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store(self::COVER_DIRECTORY, 'public');
        }

        $page = Page::create($data);

        return PageResource::make($page->load(self::RELATIONS))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/pages/{page}',
        summary: 'Read a single page, draft or not',
        security: [['sanctum' => []]],
        tags: ['Pages'],
        parameters: [
            new OA\Parameter(
                name: 'page',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'The page',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Page')],
                ),
            ),
            new OA\Response(response: 401, description: 'No or expired token'),
            new OA\Response(response: 403, description: 'Missing the pages.view privilege'),
            new OA\Response(response: 404, description: 'No such page'),
        ],
    )]
    public function show(Page $page): PageResource
    {
        return PageResource::make($page->load(self::RELATIONS));
    }

    /**
     * The slug is left alone unless the client sends a new one, so published
     * URLs do not change every time an editor tweaks the title.
     */
    #[OA\Put(
        path: '/api/pages/{page}',
        summary: 'Update a page',
        description: 'Every field is optional. The slug only changes when one is sent explicitly, so '.
            'renaming a page does not break its public URL. To replace the cover image, POST to this '.
            'same path as multipart/form-data with _method=PUT, because PHP only fills in uploaded '.
            'files on POST requests. Send remove_cover=1 to clear the existing image. '.
            'Requires the pages.update privilege.',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'menu_id', type: 'integer', example: 1),
                        new OA\Property(property: 'title', type: 'string', example: 'Annual Report 2026'),
                        new OA\Property(property: 'slug', type: 'string'),
                        new OA\Property(property: 'body', type: 'string'),
                        new OA\Property(property: 'cover_image', type: 'string', format: 'binary'),
                        new OA\Property(property: 'remove_cover', type: 'boolean', example: false),
                        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'published']),
                        new OA\Property(property: 'published_at', type: 'string', format: 'date-time', nullable: true),
                        new OA\Property(property: 'position', type: 'integer'),
                    ],
                ),
            ),
        ),
        tags: ['Pages'],
        parameters: [
            new OA\Parameter(
                name: 'page',
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
                    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Page')],
                ),
            ),
            new OA\Response(response: 401, description: 'No or expired token'),
            new OA\Response(response: 403, description: 'Missing the pages.update privilege'),
            new OA\Response(response: 404, description: 'No such page'),
            new OA\Response(
                response: 422,
                description: 'Validation failed',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
            ),
        ],
    )]
    public function update(UpdatePageRequest $request, Page $page): PageResource
    {
        $data = $request->validated();

        // Dropped so that a form posted without a file cannot blank the existing
        // cover by accident; clearing it is an explicit remove_cover flag.
        unset($data['remove_cover'], $data['cover_image']);

        if ($request->hasFile('cover_image')) {
            $this->deleteCover($page);
            $data['cover_image'] = $request->file('cover_image')->store(self::COVER_DIRECTORY, 'public');
        } elseif ($request->boolean('remove_cover')) {
            $this->deleteCover($page);
            $data['cover_image'] = null;
        }

        $page->update($data);

        return PageResource::make($page->load(self::RELATIONS));
    }

    #[OA\Delete(
        path: '/api/pages/{page}',
        summary: 'Move a page to the trash',
        description: 'A soft delete. The cover image is kept so a restore brings the page back whole. '.
            'Requires the pages.delete privilege, which a moderator does not hold.',
        security: [['sanctum' => []]],
        tags: ['Pages'],
        parameters: [
            new OA\Parameter(
                name: 'page',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Moved to the trash',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse'),
            ),
            new OA\Response(response: 401, description: 'No or expired token'),
            new OA\Response(response: 403, description: 'Missing the pages.delete privilege'),
            new OA\Response(response: 404, description: 'No such page'),
        ],
    )]
    public function destroy(Page $page): JsonResponse
    {
        $page->delete();

        return response()->json(['message' => 'Page moved to trash.']);
    }

    #[OA\Post(
        path: '/api/pages/{page}/restore',
        summary: 'Bring a page back out of the trash',
        description: 'Requires the pages.restore privilege, held by an administrator only.',
        security: [['sanctum' => []]],
        tags: ['Pages'],
        parameters: [
            new OA\Parameter(
                name: 'page',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Restored',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Page')],
                ),
            ),
            new OA\Response(response: 401, description: 'No or expired token'),
            new OA\Response(response: 403, description: 'Missing the pages.restore privilege'),
            new OA\Response(response: 404, description: 'No such page'),
        ],
    )]
    public function restore(Page $page): PageResource
    {
        $page->restore();

        return PageResource::make($page->load(self::RELATIONS));
    }

    #[OA\Delete(
        path: '/api/pages/{page}/force',
        summary: 'Delete a page for good',
        description: 'Removes the row and its cover file. Requires the pages.restore privilege.',
        security: [['sanctum' => []]],
        tags: ['Pages'],
        parameters: [
            new OA\Parameter(
                name: 'page',
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
            new OA\Response(response: 403, description: 'Missing the pages.restore privilege'),
            new OA\Response(response: 404, description: 'No such page'),
        ],
    )]
    public function forceDestroy(Page $page): JsonResponse
    {
        $this->deleteCover($page);
        $page->forceDelete();

        return response()->json(['message' => 'Page deleted permanently.']);
    }

    /**
     * @param  Builder<Page>  $query
     * @return Builder<Page>
     */
    private function applyFilters(Builder $query, IndexPageRequest $request): Builder
    {
        return $query
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $term = addcslashes(trim((string) $request->input('search')), '%_\\');

                $query->where('title', 'like', "%{$term}%");
            })
            ->when(
                $request->filled('menu_id'),
                fn (Builder $query) => $query->where('menu_id', $request->integer('menu_id'))
            )
            ->when(
                $request->filled('status'),
                fn (Builder $query) => $query->where('status', $request->input('status'))
            );
    }

    private function deleteCover(Page $page): void
    {
        if ($page->cover_image) {
            Storage::disk('public')->delete($page->cover_image);
        }
    }
}
