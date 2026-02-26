<?php

namespace App\Containers\AppSection\UserTable\Tasks;

use App\Containers\AppSection\UserTable\Data\Repositories\UserTableRepository;
use App\Containers\AppSection\UserTable\Models\UserTable;
use App\Ship\Parents\Tasks\Task as ParentTask;
use Apiato\Core\Repositories\Exceptions\ResourceNotFound;

final class FindUserTableByUserIdAndTableNameTask extends ParentTask
{
    public function __construct(
        private readonly UserTableRepository $repository,
    ) {
    }

    public function run(string $userId, string $tableName): ?UserTable
    {
        return $this->repository
            ->loadQuery($userId, $tableName)
            ->first();
    }

    public function runOrFail(string $userId, string $tableName): UserTable
    {
        $userTable = $this->run($userId, $tableName);

        if (!$userTable) {
            throw new ResourceNotFound(404, "UserTable not found for user: {$userId} and table_name: {$tableName}");
        }

        return $userTable;
    }
}
