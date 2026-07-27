<?php

use App\Models\User;
use Database\Seeders\PrivilegeSeeder;
use Database\Seeders\RoleSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->seed([PrivilegeSeeder::class, RoleSeeder::class]);
});

it('issues a token and reports the privileges the user holds', function (): void {
    $user = userWithRole('moderator');

    $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ])
        ->assertOk()
        ->assertJsonStructure([
            'data' => ['id', 'name', 'email', 'roles', 'privileges'],
            'token',
        ])
        ->assertJsonPath('data.privileges', [
            'menus.view',
            'pages.create',
            'pages.update',
            'pages.view',
        ]);
});

it('never returns the password hash', function (): void {
    $user = userWithRole('admin');

    $response = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertOk();

    expect($response->json('data'))->not->toHaveKey('password');
});

it('rejects a wrong password', function (): void {
    $user = User::factory()->create();

    $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'not-the-password',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

it('rejects an email that does not exist', function (): void {
    $this->postJson('/api/auth/login', [
        'email' => 'nobody@cms.test',
        'password' => 'password',
    ])->assertStatus(422);
});

it('refuses a protected endpoint without a token', function (): void {
    $this->getJson('/api/pages')->assertUnauthorized();
    $this->getJson('/api/auth/me')->assertUnauthorized();
});

it('returns the signed in user', function (): void {
    $user = userWithRole('admin');

    Sanctum::actingAs($user);

    $this->getJson('/api/auth/me')
        ->assertOk()
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonPath('data.roles.0.slug', 'admin');
});

it('revokes the token on logout', function (): void {
    $user = userWithRole('admin');

    $token = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->json('token');

    $this->withToken($token)->postJson('/api/auth/logout')->assertOk();

    expect($user->tokens()->count())->toBe(0);

    // The guard holds on to the user it resolved a moment ago, so forget it
    // before replaying the revoked token against the API.
    $this->app['auth']->forgetGuards();

    $this->withToken($token)->getJson('/api/auth/me')->assertUnauthorized();
});

it('throttles repeated login attempts', function (): void {
    $user = User::factory()->create();

    foreach (range(1, 6) as $ignored) {
        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong',
        ])->assertStatus(422);
    }

    $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'wrong',
    ])->assertStatus(429);
});
