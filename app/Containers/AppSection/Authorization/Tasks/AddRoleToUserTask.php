<?php

namespace App\Containers\AppSection\Authorization\Tasks;

use App\Containers\AppSection\Authorization\Data\Repositories\RoleRepository;
use App\Containers\AppSection\Authorization\Enums\Role as RoleEnum;
use App\Containers\AppSection\User\Models\User;
use App\Ship\Parents\Tasks\Task as ParentTask;

final class AddRoleToUserTask extends ParentTask
{
    public function __construct(
        private readonly RoleRepository $repository,
    ) {
    }

    public function run(User $user, RoleEnum $roleEnum): User
    {
        return $this->repository->addRoleToUser($user, $roleEnum);
    }
}
