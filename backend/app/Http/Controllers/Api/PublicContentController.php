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

class PublicContentController extends Controller
{
    /**
     * The menu tree with the pages that belong under each item.
     *
     * Only active menu items are selected, so switching a parent off also hides
     * everything nested beneath it: its children no longer have a parent to be
     * attached to while the tree is assembled.
     */
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
