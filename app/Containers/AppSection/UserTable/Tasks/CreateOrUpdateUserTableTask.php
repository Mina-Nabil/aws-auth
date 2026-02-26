<?php

namespace App\Containers\AppSection\UserTable\Tasks;

use App\Containers\AppSection\UserTable\Data\DTOs\UserTableDTO;
use App\Containers\AppSection\UserTable\Data\Repositories\UserTableRepository;
use App\Containers\AppSection\UserTable\Models\UserTable;
use App\Containers\AppSection\UserTable\Tasks\FindUserTableByUserIdAndTableNameTask;
use App\Ship\Parents\Tasks\Task as ParentTask;

final class CreateOrUpdateUserTableTask extends ParentTask
{
    public function __construct(
        private readonly UserTableRepository $repository,
        private readonly FindUserTableByUserIdAndTableNameTask $findUserTableByUserIdAndTableNameTask,
    ) {
    }

    public function run(UserTableDTO $userTableDTO): UserTable
    {
        // Try to find existing record
        $existingUserTable = $this->findUserTableByUserIdAndTableNameTask->run(
            $userTableDTO->user_id,
            $userTableDTO->table_name
        );

        if ($existingUserTable) {
            // Update existing record
            $columns = $userTableDTO->columns ?? [];
            return $this->repository->update(['columns' => $columns], $existingUserTable->id);
        }

        // Create new record
        $createData = $userTableDTO->getCreateArray();
        return $this->repository->create($createData);
    }
}
