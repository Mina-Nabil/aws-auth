<?php

namespace App\Containers\AppSection\UserTable\Data\Factories;

use App\Containers\AppSection\UserTable\Models\UserTable;
use App\Containers\AppSection\User\Models\User;
use App\Ship\Parents\Factories\Factory as ParentFactory;

/**
 * @template TModel of UserTable
 *
 * @extends ParentFactory<TModel>
 */
final class UserTableFactory extends ParentFactory
{
    /** @var class-string<TModel> */
    protected $model = UserTable::class;

    public function definition(): array
    {
        $columnCount = fake()->numberBetween(1, 10);
        $columns = [];

        for ($i = 0; $i < $columnCount; $i++) {
            $columns[] = [
                'name' => fake()->word(),
                'type' => fake()->randomElement(['string', 'integer', 'boolean', 'date']),
                'required' => fake()->boolean(),
            ];
        }

        return [
            'user_id' => User::factory(),
            'table_name' => fake()->unique()->word() . '_' . fake()->word(),
            'columns' => $columns,
        ];
    }

    public function forUser(User $user): self
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    public function withTableName(string $tableName): self
    {
        return $this->state(fn (array $attributes) => [
            'table_name' => $tableName,
        ]);
    }

    public function withColumns(array $columns): self
    {
        return $this->state(fn (array $attributes) => [
            'columns' => $columns,
        ]);
    }
}
