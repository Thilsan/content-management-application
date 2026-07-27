<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Order matters: roles need the privileges, users need the roles, and the
     * demo pages are stamped with the administrator as their author.
     */
    public function run(): void
    {
        $this->call([
            PrivilegeSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            ContentSeeder::class,
        ]);
    }
}
