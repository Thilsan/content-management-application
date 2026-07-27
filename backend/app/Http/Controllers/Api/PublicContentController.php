<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MenuResource;
use App\Http\Resources\PageResource;
use App\Http\Resources\PageSummaryResource;
use App\Models\Menu;
use App\Models\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

class PublicContentController extends Controller
{
    /**
     * The menu tree with the pages that belong under each item.
     *
     * Only active menu items are selected, so switching a parent off also hides
     * everything nested beneath it: its children no longer have a parent to be
     * attached to while the tree is assembled.
     */
    #[OA\Get(
        path: '/api/public/menu',
        summary: 'The public menu tree with its pages',
        description: 'No token required. Inactive menu items are left out, along with everything '.
            'nested under them. Each item carries only the pages that are published and due.',
        tags: ['Public'],
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
        ],
    )]
    public function menu(): AnonymousResourceCollection
    {
        $tree = Menu::tree(
            Menu::query()
                ->active()
                ->with(['pages' => fn (HasMany $query) => $query
                    ->visible()
                    ->orderBy('position')
                    ->orderBy('title'),
                ])
        );

        return MenuResource::collection($tree);
    }

    #[OA\Get(
        path: '/api/public/pages',
        summary: 'List the pages the public may read',
        description: 'Drafts, pages dated in the future and pages under an inactive menu item are '.
            'all excluded. Bodies are omitted from the listing.',
        tags: ['Public'],
        parameters: [
            new OA\Parameter(
                name: 'menu',
                description: 'Slug of a menu item to filter by.',
                in: 'query',
                schema: new OA\Schema(type: 'string', example: 'news'),
            ),
            new OA\Parameter(
                name: 'search',
                in: 'query',
                schema: new OA\Schema(type: 'string'),
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                schema: new OA\Schema(type: 'integer', maximum: 100, minimum: 1, default: 12),
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
                            items: new OA\Items(ref: '#/components/schemas/PageSummary'),
                        ),
                        new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                    ],
                ),
            ),
        ],
    )]
    public function pages(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'menu' => ['nullable', 'string', 'max:140'],
            'search' => ['nullable', 'string', 'max:200'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $pages = Page::query()
            ->visible()
            ->whereHas('menu', fn (Builder $query) => $query->active())
            ->when($request->filled('menu'), fn (Builder $query) => $query
                ->whereHas('menu', fn (Builder $menu) => $menu->where('slug', $request->input('menu')))
            )
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $term = addcslashes(trim((string) $request->input('search')), '%_\\');

                $query->where('title', 'like', "%{$term}%");
            })
            ->orderBy('position')
            ->orderBy('title')
            ->paginate($request->integer('per_page', 12))
            ->withQueryString();

        return PageSummaryResource::collection($pages);
    }

    #[OA\Get(
        path: '/api/public/pages/{slug}',
        summary: 'Read one published page',
        description: 'Answers 404 for a draft, for a page whose publish date has not arrived, and '.
            'for a page filed under an inactive menu item. This is where scheduled publishing is '.
            'enforced for readers.',
        tags: ['Public'],
        parameters: [
            new OA\Parameter(
                name: 'slug',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'who-we-are'),
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
            new OA\Response(response: 404, description: 'Not published, not due, or no such page'),
        ],
    )]
    public function page(string $slug): PageResource
    {
        $page = Page::query()
            ->visible()
            ->whereHas('menu', fn (Builder $query) => $query->active())
            ->with('menu')
            ->where('slug', $slug)
            ->firstOrFail();

        return PageResource::make($page);
    }
}
