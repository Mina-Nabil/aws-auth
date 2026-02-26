<?php

namespace App\Containers\AppSection\UserTable\Data\Repositories;

use App\Containers\AppSection\UserTable\Models\UserTable;
use App\Ship\Parents\Repositories\Repository as ParentRepository;

/**
 * @template TModel of UserTable
 *
 * @extends ParentRepository<TModel>
 */
final class UserTableRepository extends ParentRepository
{
    protected $fieldSearchable = [
        'table_name' => '=',
        'user_id' => '=',
    ];

    public function loadQuery(?string $userId = null, ?string $tableName = null): self
    {
        return $this->scopeQuery(function ($query) use ($userId, $tableName) {
            if ($userId !== null) {
                $query = $query->byUserId($userId);
            }
            if ($tableName !== null) {
                $query = $query->byTableName($tableName);
            }
            return $query;
        });
    }

    public function model(): string
    {
        return UserTable::class;
    }
}
