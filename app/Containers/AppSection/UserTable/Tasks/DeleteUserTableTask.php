<?php

namespace App\Containers\AppSection\UserTable\Tasks;

use App\Containers\AppSection\UserTable\Data\Repositories\UserTableRepository;
use App\Containers\AppSection\UserTable\Tasks\FindUserTableByUserIdAndTableNameTask;
use App\Ship\Parents\Tasks\Task as ParentTask;

final class DeleteUserTableTask extends ParentTask
{
    public function __construct(
        private readonly UserTableRepository $repository,
        private readonly FindUserTableByUserIdAndTableNameTask $findUserTableByUserIdAndTableNameTask,
    ) {
    }

    public function run(string $userId, string $tableName): bool
    {
        $userTable = $this->findUserTableByUserIdAndTableNameTask->runOrFail($userId, $tableName);
        return $this->repository->delete($userTable->id);
    }
}
