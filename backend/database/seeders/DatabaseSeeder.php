<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Order matters: roles need the privileges, users need the roles, and the
     * demo pages are stamped with the administrator as their author.
     *
     * Model events are left on deliberately (no WithoutModelEvents here):
     * ContentSeeder relies on Page's own saving hook to work out is_live for
     * each seeded page, the same hook a real save through the API goes
     * through. Muting it would leave every seeded page flagged as not live.
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
