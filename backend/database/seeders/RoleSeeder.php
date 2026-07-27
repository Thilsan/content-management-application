<?php

namespace Database\Seeders;

use App\Models\Privilege;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Everything a moderator may do. Anything absent from this list is denied,
     * which is what keeps a moderator away from deletes and user management.
     *
     * @var list<string>
     */
    private array $moderatorPrivileges = [
        'pages.view',
        'pages.create',
        'pages.update',
        'menus.view',
    ];

    public function run(): void
    {
        $admin = Role::updateOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Administrator',
                'description' => 'Full access to pages, menus, users, roles and privileges.',
            ],
        );

        $admin->privileges()->sync(Privilege::pluck('id'));

        $moderator = Role::updateOrCreate(
            ['slug' => 'moderator'],
            [
                'name' => 'Moderator',
                'description' => 'May list, add and edit pages, but not delete them or manage access.',
            ],
        );

        $moderator->privileges()->sync(
            Privilege::whereIn('name', $this->moderatorPrivileges)->pluck('id')
        );
    }
}
