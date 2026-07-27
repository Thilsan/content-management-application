<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\PrivilegeSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->seed([PrivilegeSeeder::class, RoleSeeder::class]);

    $this->admin = userWithRole('admin');

    Sanctum::actingAs($this->admin);
});

it('creates a user and attaches the chosen roles', function (): void {
    $moderator = Role::where('slug', 'moderator')->firstOrFail();

    $this->postJson('/api/users', [
        'name' => 'New Editor',
        'email' => 'editor@cms.test',
        'password' => 'a-long-enough-password',
        'roles' => [$moderator->id],
    ])
        ->assertCreated()
        ->assertJsonPath('data.email', 'editor@cms.test')
        ->assertJsonPath('data.roles.0.slug', 'moderator')
        ->assertJsonPath('data.privileges', [
            'menus.view',
            'pages.create',
            'pages.update',
            'pages.view',
        ]);
});

it('hashes the password it is given', function (): void {
    $this->postJson('/api/users', [
        'name' => 'New Editor',
        'email' => 'editor@cms.test',
        'password' => 'a-long-enough-password',
    ])->assertCreated();

    $user = User::firstWhere('email', 'editor@cms.test');

    expect($user->password)->not->toBe('a-long-enough-password')
        ->and(Hash::check('a-long-enough-password', $user->password))->toBeTrue();
});

it('rejects a duplicate email and a short password', function (): void {
    $existing = User::factory()->create();

    $this->postJson('/api/users', [
        'name' => 'Clash',
        'email' => $existing->email,
        'password' => 'short',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password']);
});

it('leaves the password untouched when the field is left blank', function (): void {
    $user = User::factory()->create();
    $hash = $user->password;

    $this->putJson("/api/users/{$user->id}", [
        'name' => 'Renamed Person',
        'password' => null,
    ])->assertOk();

    expect($user->fresh()->password)->toBe($hash)
        ->and($user->fresh()->name)->toBe('Renamed Person');
});

it('replaces the roles a user holds on update', function (): void {
    $user = userWithRole('moderator');
    $adminRole = Role::where('slug', 'admin')->firstOrFail();

    $this->putJson("/api/users/{$user->id}", ['roles' => [$adminRole->id]])
        ->assertOk()
        ->assertJsonPath('data.roles.0.slug', 'admin');

    expect($user->fresh()->roles)->toHaveCount(1);
});

it('searches users by name or email', function (): void {
    User::factory()->create(['name' => 'Amara Perera', 'email' => 'amara@cms.test']);
    User::factory()->create(['name' => 'Bilal Khan', 'email' => 'bilal@cms.test']);

    $this->getJson('/api/users?search=amara')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson('/api/users?search=bilal@cms.test')->assertOk()->assertJsonCount(1, 'data');
});

it('will not let an administrator delete their own account', function (): void {
    $this->deleteJson("/api/users/{$this->admin->id}")
        ->assertStatus(422)
        ->assertJsonValidationErrors('user');

    expect(User::find($this->admin->id))->not->toBeNull();
});

it('deletes another user', function (): void {
    $user = User::factory()->create();

    $this->deleteJson("/api/users/{$user->id}")->assertOk();

    expect(User::find($user->id))->toBeNull();
});
