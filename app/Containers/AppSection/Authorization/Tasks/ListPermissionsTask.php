<?php

namespace App\Containers\AppSection\Authorization\Tasks;

use App\Containers\AppSection\Authorization\Data\Collections\PermissionCollection;
use App\Containers\AppSection\Authorization\Data\Repositories\PermissionRepository;
use App\Ship\Parents\Tasks\Task as ParentTask;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListPermissionsTask extends ParentTask
{
    public function __construct(
        private readonly PermissionRepository $repository,
    ) {
    }

    public function run(bool $paginate = true, ?string $guard = null): LengthAwarePaginator|PermissionCollection
    {
        $query = $this->repository->addRequestCriteria();

        if ($guard !== null) {
            $query->whereGuard($guard);
        }

        return $paginate ? $query->paginate() : $query->get();
    }
}
