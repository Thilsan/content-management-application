<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Demo accounts. The passwords are hashed by the model cast.
     *
     * @var list<array{name: string, email: string, role: string}>
     */
    private array $accounts = [
        ['name' => 'Site Administrator', 'email' => 'admin@cms.com', 'role' => 'admin'],
        ['name' => 'Content Moderator', 'email' => 'moderator@cms.com', 'role' => 'moderator'],
    ];

    public function run(): void
    {
        foreach ($this->accounts as $account) {
            $user = User::updateOrCreate(
                ['email' => $account['email']],
                ['name' => $account['name'], 'password' => '12345'],
            );

            $user->roles()->sync(
                Role::where('slug', $account['role'])->pluck('id')
            );
        }
    }
}
