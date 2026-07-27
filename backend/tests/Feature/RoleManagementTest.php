<?php

use App\Models\Page;
use App\Models\Privilege;
use App\Models\Role;
use Database\Seeders\PrivilegeSeeder;
use Database\Seeders\RoleSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->seed([PrivilegeSeeder::class, RoleSeeder::class]);

    Sanctum::actingAs(userWithRole('admin'));
});

it('lists the seeded roles with their privileges', function (): void {
    $this->getJson('/api/roles')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.slug', 'admin');
});

it('creates a role with a generated slug and the chosen privileges', function (): void {
    $ids = Privilege::whereIn('name', ['pages.view', 'pages.update'])->pluck('id')->all();

    $this->postJson('/api/roles', [
        'name' => 'Copy Editor',
        'description' => 'Reads and tidies pages.',
        'privileges' => $ids,
    ])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'copy-editor')
        ->assertJsonCount(2, 'data.privileges');
});

it('changes what a role may do by syncing its privileges', function (): void {
    $role = Role::where('slug', 'moderator')->firstOrFail();
    $viewOnly = Privilege::where('name', 'pages.view')->value('id');

    $this->putJson("/api/roles/{$role->id}", ['privileges' => [$viewOnly]])
        ->assertOk()
        ->assertJsonCount(1, 'data.privileges');

    // A user holding that role now loses the ability to add pages.
    Sanctum::actingAs(userWithRole('moderator'));

    $this->getJson('/api/pages')->assertOk();
    $this->postJson('/api/pages', [])->assertForbidden();
});

it('refuses to delete a role that still has users', function (): void {
    $role = Role::where('slug', 'moderator')->firstOrFail();
    userWithRole('moderator');

    $this->deleteJson("/api/roles/{$role->id}")
        ->assertStatus(422)
        ->assertJsonValidationErrors('role');

    expect(Role::find($role->id))->not->toBeNull();
});

it('deletes a role once nobody holds it', function (): void {
    $role = Role::factory()->create();

    $this->deleteJson("/api/roles/{$role->id}")->assertOk();

    expect(Role::find($role->id))->toBeNull();
});

it('lists the privilege catalogue grouped by area', function (): void {
    $this->getJson('/api/privileges')
        ->assertOk()
        ->assertJsonCount(22, 'data')
        ->assertJsonPath('data.0.group', 'menus');
});

it('enforces the group.action naming convention on a new privilege', function (): void {
    $this->postJson('/api/privileges', [
        'name' => 'Reports Export',
        'label' => 'Export reports',
        'group' => 'reports',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('name');
});

it('makes a newly created privilege enforceable straight away', function (): void {
    $this->postJson('/api/privileges', [
        'name' => 'reports.export',
        'label' => 'Export reports',
        'group' => 'reports',
    ])->assertCreated();

    expect(Privilege::where('name', 'reports.export')->exists())->toBeTrue();

    // The gate resolves privilege names dynamically, so the new one works at once.
    $user = userWithPrivileges(['reports.export']);

    expect($user->can('reports.export'))->toBeTrue()
        ->and($user->can('pages.delete'))->toBeFalse();
});

it('deletes a privilege and detaches it from its roles', function (): void {
    $privilege = Privilege::where('name', 'pages.delete')->firstOrFail();
    $admin = Role::where('slug', 'admin')->firstOrFail();

    expect($admin->privileges()->where('name', 'pages.delete')->exists())->toBeTrue();

    $this->deleteJson("/api/privileges/{$privilege->id}")->assertOk();

    expect($admin->privileges()->where('name', 'pages.delete')->exists())->toBeFalse();

    // With the privilege gone, even an administrator can no longer delete a page.
    $page = Page::factory()->create();

    Sanctum::actingAs(userWithRole('admin'));

    $this->deleteJson("/api/pages/{$page->id}")->assertForbidden();
});
