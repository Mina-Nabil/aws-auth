<?php

namespace App\Containers\AppSection\Authorization\Tasks;

use App\Containers\AppSection\Authorization\Data\Repositories\RoleRepository;
use App\Ship\Parents\Tasks\Task as ParentTask;

final class DeleteRoleTask extends ParentTask
{
    public function __construct(
        private readonly RoleRepository $repository,
    ) {
    }

    public function run(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
