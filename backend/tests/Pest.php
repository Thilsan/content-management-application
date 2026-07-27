<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
 * The login route is rate limited, and the limiter keeps its hits in the cache.
 * Flushing between tests stops one test's failed logins from tripping the next.
 */
pest()->beforeEach(function (): void {
    Cache::flush();
})->in('Feature');

/**
 * A user holding a throwaway role that grants exactly the privileges listed.
 * Useful for proving a rule without leaning on the seeded roles.
 *
 * @param  list<string>  $privileges
 */
function userWithPrivileges(array $privileges): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::factory()->withPrivileges($privileges)->create());

    return $user;
}

/**
 * A user holding one of the seeded roles, so tests assert against the access
 * control the application actually ships with.
 */
function userWithRole(string $slug): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::where('slug', $slug)->firstOrFail());

    return $user;
}
