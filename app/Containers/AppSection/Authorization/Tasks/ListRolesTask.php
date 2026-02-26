<?php

namespace App\Containers\AppSection\Authorization\Tasks;

use App\Containers\AppSection\Authorization\Data\Collections\RoleCollection;
use App\Containers\AppSection\Authorization\Data\Repositories\RoleRepository;
use App\Ship\Parents\Tasks\Task as ParentTask;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListRolesTask extends ParentTask
{
    public function __construct(
        private readonly RoleRepository $repository,
    ) {
    }

    public function run(?string $guard = null): LengthAwarePaginator|RoleCollection
    {
        $query = $this->repository->addRequestCriteria();

        if ($guard !== null) {
            $query->whereGuard($guard);
        }

        return $query->paginate();
    }
}
