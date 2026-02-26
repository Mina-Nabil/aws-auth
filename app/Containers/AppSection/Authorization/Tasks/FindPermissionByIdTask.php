<?php

namespace App\Containers\AppSection\Authorization\Tasks;

use App\Containers\AppSection\Authorization\Data\Repositories\PermissionRepository;
use App\Containers\AppSection\Authorization\Models\Permission;
use App\Ship\Parents\Tasks\Task as ParentTask;

final class FindPermissionByIdTask extends ParentTask
{
    public function __construct(
        private readonly PermissionRepository $repository,
    ) {
    }

    public function run(string $id): Permission
    {
        return $this->repository->findOrFail($id);
    }
}
