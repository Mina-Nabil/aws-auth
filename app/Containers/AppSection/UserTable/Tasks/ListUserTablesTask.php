<?php

namespace App\Containers\AppSection\UserTable\Tasks;

use App\Containers\AppSection\UserTable\Data\Repositories\UserTableRepository;
use App\Ship\Parents\Tasks\Task as ParentTask;
use Illuminate\Database\Eloquent\Collection;

final class ListUserTablesTask extends ParentTask
{
    public function __construct(
        private readonly UserTableRepository $repository,
    ) {
    }

    public function run(string $userId, ?string $tableName = null): Collection
    {
        return $this->repository
            ->loadQuery($userId, $tableName)
            ->get();
    }
}
