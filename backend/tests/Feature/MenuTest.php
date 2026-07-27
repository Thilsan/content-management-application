<?php

use App\Models\Menu;
use App\Models\Page;
use Database\Seeders\PrivilegeSeeder;
use Database\Seeders\RoleSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->seed([PrivilegeSeeder::class, RoleSeeder::class]);

    Sanctum::actingAs(userWithRole('admin'));
});

it('returns the menu as a nested tree', function (): void {
    $parent = Menu::factory()->create(['position' => 0]);
    $child = Menu::factory()->childOf($parent)->create(['position' => 0]);
    $grandchild = Menu::factory()->childOf($child)->create(['position' => 0]);

    $this->getJson('/api/menus')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $parent->id)
        ->assertJsonPath('data.0.children.0.id', $child->id)
        ->assertJsonPath('data.0.children.0.children.0.id', $grandchild->id);
});

it('orders siblings by position', function (): void {
    $second = Menu::factory()->create(['title' => 'Second', 'position' => 1]);
    $first = Menu::factory()->create(['title' => 'First', 'position' => 0]);

    $this->getJson('/api/menus')
        ->assertOk()
        ->assertJsonPath('data.0.id', $first->id)
        ->assertJsonPath('data.1.id', $second->id);
});

it('reorders and reparents menu items', function (): void {
    $first = Menu::factory()->create(['position' => 0]);
    $second = Menu::factory()->create(['position' => 1]);

    $this->postJson('/api/menus/reorder', [
        'items' => [
            ['id' => $second->id, 'parent_id' => null, 'position' => 0],
            ['id' => $first->id, 'parent_id' => $second->id, 'position' => 0],
        ],
    ])
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $second->id)
        ->assertJsonPath('data.0.children.0.id', $first->id);

    expect($first->fresh()->parent_id)->toBe($second->id);
});

it('rejects a circular menu order', function (): void {
    $parent = Menu::factory()->create();
    $child = Menu::factory()->childOf($parent)->create();

    $this->postJson('/api/menus/reorder', [
        'items' => [
            ['id' => $parent->id, 'parent_id' => $child->id, 'position' => 0],
            ['id' => $child->id, 'parent_id' => $parent->id, 'position' => 0],
        ],
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('items');

    expect($parent->fresh()->parent_id)->toBeNull();
});

it('refuses to nest a menu item inside its own child', function (): void {
    $parent = Menu::factory()->create();
    $child = Menu::factory()->childOf($parent)->create();

    $this->putJson("/api/menus/{$parent->id}", ['parent_id' => $child->id])
        ->assertStatus(422)
        ->assertJsonValidationErrors('parent_id');
});

it('refuses to delete a menu item that still holds pages', function (): void {
    $menu = Menu::factory()->create();
    Page::factory()->create(['menu_id' => $menu->id]);

    $this->deleteJson("/api/menus/{$menu->id}")
        ->assertStatus(422)
        ->assertJsonValidationErrors('menu');

    expect(Menu::find($menu->id))->not->toBeNull();
});

it('refuses to delete a branch whose child holds pages', function (): void {
    $parent = Menu::factory()->create();
    $child = Menu::factory()->childOf($parent)->create();
    Page::factory()->create(['menu_id' => $child->id]);

    $this->deleteJson("/api/menus/{$parent->id}")->assertStatus(422);

    expect(Menu::find($parent->id))->not->toBeNull()
        ->and(Menu::find($child->id))->not->toBeNull();
});

it('counts a trashed page as still occupying its menu item', function (): void {
    $menu = Menu::factory()->create();
    $page = Page::factory()->create(['menu_id' => $menu->id]);
    $page->delete();

    $this->deleteJson("/api/menus/{$menu->id}")->assertStatus(422);
});

it('deletes an empty menu item with its children', function (): void {
    $parent = Menu::factory()->create();
    $child = Menu::factory()->childOf($parent)->create();

    $this->deleteJson("/api/menus/{$parent->id}")->assertOk();

    expect(Menu::find($parent->id))->toBeNull()
        ->and(Menu::find($child->id))->toBeNull();
});

it('creates a menu item at the end of its level', function (): void {
    Menu::factory()->create(['position' => 0]);
    Menu::factory()->create(['position' => 1]);

    $this->postJson('/api/menus', ['title' => 'Contact'])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'contact')
        ->assertJsonPath('data.position', 2);
});
