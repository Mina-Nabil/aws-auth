<?php

namespace App\Containers\AppSection\Authorization\Data\Factories;

use App\Containers\AppSection\Authorization\Models\Permission;
use App\Ship\Parents\Factories\Factory as ParentFactory;

/**
 * @template TModel of Permission
 *
 * @extends ParentFactory<TModel>
 */
final class PermissionFactory extends ParentFactory
{
    /** @var class-string<TModel> */
    protected $model = Permission::class;

    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'name' => fake()->unique()->firstName(),
            'guard_name' => 'api',
            'display_name' => fake()->unique()->firstName(),
            'description' => fake()->unique()->firstName(),
            'group' => fake()->unique()->firstName(),
            'is_active' => fake()->boolean(),
            'version' => fake()->numberBetween(1, 3),
        ];
    }

    public function withGuard(string $guard): self
    {
        return $this->state(function (array $attributes) use ($guard) {
            return [
                'guard_name' => $guard,
            ];
        });
    }
}
