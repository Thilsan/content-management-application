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

class MenuController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return MenuResource::collection(
            Menu::tree(Menu::query()->withCount('pages'))
        );
    }

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

    public function update(UpdateMenuRequest $request, Menu $menu): MenuResource
    {
        $menu->update($request->validated());

        return MenuResource::make($menu->loadCount('pages'));
    }

    /**
     * Deleting a branch cascades to its children, so refuse while any page in the
     * branch still points at it rather than letting the database reject it.
     */
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
