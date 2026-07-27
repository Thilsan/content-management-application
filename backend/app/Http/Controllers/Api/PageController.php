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

class PageController extends Controller
{
    private const COVER_DIRECTORY = 'covers';

    private const RELATIONS = ['menu', 'creator', 'editor'];

    public function index(IndexPageRequest $request): AnonymousResourceCollection
    {
        $pages = $this->applyFilters(Page::query(), $request)
            ->with(self::RELATIONS)
            ->latest('id')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return PageResource::collection($pages);
    }

    public function trashed(IndexPageRequest $request): AnonymousResourceCollection
    {
        $pages = $this->applyFilters(Page::onlyTrashed(), $request)
            ->with(self::RELATIONS)
            ->orderByDesc('deleted_at')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return PageResource::collection($pages);
    }

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

    public function show(Page $page): PageResource
    {
        return PageResource::make($page->load(self::RELATIONS));
    }

    /**
     * The slug is left alone unless the client sends a new one, so published
     * URLs do not change every time an editor tweaks the title.
     */
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

    public function destroy(Page $page): JsonResponse
    {
        $page->delete();

        return response()->json(['message' => 'Page moved to trash.']);
    }

    public function restore(Page $page): PageResource
    {
        $page->restore();

        return PageResource::make($page->load(self::RELATIONS));
    }

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
