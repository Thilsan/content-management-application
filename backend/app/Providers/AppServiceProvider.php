<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Each privilege name doubles as a gate ability, so authorization reads
        // from the privileges table instead of from role names in code. Changing
        // what a role may do is then a data change rather than a deploy.
        Gate::before(function (User $user, string $ability) {
            return $user->hasPrivilege($ability) ?: null;
        });
    }
}
