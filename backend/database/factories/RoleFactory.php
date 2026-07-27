<?php

namespace Database\Factories;

use App\Models\Privilege;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(2, true));

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'description' => fake()->sentence(),
        ];
    }

    /**
     * Grant the role a set of privileges, creating any that do not exist yet.
     *
     * @param  list<string>  $names
     */
    public function withPrivileges(array $names): static
    {
        return $this->afterCreating(function (Role $role) use ($names): void {
            $privileges = collect($names)->map(fn (string $name) => Privilege::firstOrCreate(
                ['name' => $name],
                ['label' => Str::headline($name), 'group' => Str::before($name, '.')],
            ));

            $role->privileges()->syncWithoutDetaching($privileges->pluck('id'));
        });
    }
}
