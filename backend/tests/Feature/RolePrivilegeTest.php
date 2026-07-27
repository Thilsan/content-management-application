<?php

use App\Models\Menu;
use App\Models\Page;
use App\Models\Privilege;
use App\Models\User;
use Database\Seeders\PrivilegeSeeder;
use Database\Seeders\RoleSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->seed([PrivilegeSeeder::class, RoleSeeder::class]);
});

it('lets a moderator list, add and edit pages', function (): void {
    Sanctum::actingAs(userWithRole('moderator'));
    $menu = Menu::factory()->create();

    $this->getJson('/api/pages')->assertOk();

    $created = $this->postJson('/api/pages', [
        'menu_id' => $menu->id,
        'title' => 'Draft from a moderator',
        'body' => '<p>Work in progress.</p>',
        'status' => 'draft',
    ])->assertCreated();

    $this->putJson("/api/pages/{$created->json('data.id')}", [
        'title' => 'Edited by a moderator',
    ])
        ->assertOk()
        ->assertJsonPath('data.title', 'Edited by a moderator');
});

it('stops a moderator deleting a page', function (): void {
    $page = Page::factory()->create();

    Sanctum::actingAs(userWithRole('moderator'));

    $this->deleteJson("/api/pages/{$page->id}")->assertForbidden();

    expect($page->fresh()->trashed())->toBeFalse();
});

it('stops a moderator reaching the trash', function (): void {
    $page = Page::factory()->create();
    $page->delete();

    Sanctum::actingAs(userWithRole('moderator'));

    $this->getJson('/api/pages/trashed')->assertForbidden();
    $this->postJson("/api/pages/{$page->id}/restore")->assertForbidden();
    $this->deleteJson("/api/pages/{$page->id}/force")->assertForbidden();

    expect($page->fresh()->trashed())->toBeTrue();
});

it('keeps a moderator out of user, role and privilege management', function (): void {
    Sanctum::actingAs(userWithRole('moderator'));

    $this->getJson('/api/users')->assertForbidden();
    $this->postJson('/api/users', [])->assertForbidden();
    $this->getJson('/api/roles')->assertForbidden();
    $this->postJson('/api/roles', [])->assertForbidden();
    $this->getJson('/api/privileges')->assertForbidden();
});

it('stops a moderator changing the menu', function (): void {
    $menu = Menu::factory()->create();

    Sanctum::actingAs(userWithRole('moderator'));

    // A moderator may read the menu, because pages have to be filed under it.
    $this->getJson('/api/menus')->assertOk();

    $this->postJson('/api/menus', ['title' => 'New section'])->assertForbidden();
    $this->putJson("/api/menus/{$menu->id}", ['title' => 'Renamed'])->assertForbidden();
    $this->deleteJson("/api/menus/{$menu->id}")->assertForbidden();
    $this->postJson('/api/menus/reorder', ['items' => []])->assertForbidden();
});

it('lets an administrator delete and restore a page', function (): void {
    $page = Page::factory()->create();

    Sanctum::actingAs(userWithRole('admin'));

    $this->deleteJson("/api/pages/{$page->id}")->assertOk();
    expect($page->fresh()->trashed())->toBeTrue();

    $this->postJson("/api/pages/{$page->id}/restore")->assertOk();
    expect($page->fresh()->trashed())->toBeFalse();
});

it('reads permissions from the privileges table rather than the role name', function (): void {
    $user = userWithPrivileges(['pages.view']);
    $page = Page::factory()->create();

    Sanctum::actingAs($user);
    $this->deleteJson("/api/pages/{$page->id}")->assertForbidden();

    // Same user, same role, one extra row in the pivot table.
    $user->roles->first()->privileges()->attach(
        Privilege::where('name', 'pages.delete')->value('id')
    );

    Sanctum::actingAs($user->fresh());
    $this->deleteJson("/api/pages/{$page->id}")->assertOk();

    expect($page->fresh()->trashed())->toBeTrue();
});

it('denies a user who holds no roles at all', function (): void {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/pages')->assertForbidden();
    $this->getJson('/api/menus')->assertForbidden();
});
