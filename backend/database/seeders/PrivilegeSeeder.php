<?php

namespace Database\Seeders;

use App\Models\Privilege;
use Illuminate\Database\Seeder;

class PrivilegeSeeder extends Seeder
{
    /**
     * The privilege catalogue. Each name is also the gate ability checked by the
     * can: middleware in routes/api.php.
     *
     * @var array<string, array<string, string>>
     */
    private array $catalogue = [
        'pages' => [
            'pages.view' => 'List pages',
            'pages.create' => 'Add pages',
            'pages.update' => 'Edit pages',
            'pages.delete' => 'Delete pages',
            'pages.restore' => 'Restore or permanently delete pages',
        ],
        'menus' => [
            'menus.view' => 'List menu items',
            'menus.create' => 'Add menu items',
            'menus.update' => 'Edit menu items',
            'menus.delete' => 'Delete menu items',
            'menus.reorder' => 'Reorder the menu',
        ],
        'users' => [
            'users.view' => 'List users',
            'users.create' => 'Add users',
            'users.update' => 'Edit users',
            'users.delete' => 'Delete users',
        ],
        'roles' => [
            'roles.view' => 'List roles',
            'roles.create' => 'Add roles',
            'roles.update' => 'Edit roles',
            'roles.delete' => 'Delete roles',
        ],
        'privileges' => [
            'privileges.view' => 'List privileges',
            'privileges.create' => 'Add privileges',
            'privileges.update' => 'Edit privileges',
            'privileges.delete' => 'Delete privileges',
        ],
    ];

    public function run(): void
    {
        foreach ($this->catalogue as $group => $privileges) {
            foreach ($privileges as $name => $label) {
                Privilege::updateOrCreate(
                    ['name' => $name],
                    ['label' => $label, 'group' => $group],
                );
            }
        }
    }
}
