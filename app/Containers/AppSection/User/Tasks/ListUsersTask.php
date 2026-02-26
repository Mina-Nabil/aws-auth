<?php

namespace App\Containers\AppSection\User\Tasks;

use App\Containers\AppSection\User\Data\Repositories\UserRepository;
use App\Ship\Parents\Tasks\Task as ParentTask;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class ListUsersTask extends ParentTask
{
    public function __construct(
        private readonly UserRepository $repository,
    ) {
    }

    /**
     * List users with optional filtering using repository scopes
     *
     * @param array|null $ids Filter by user IDs
     * @param string|null $role Filter by user role
     * @param bool|null $isDisabled Filter by disabled status
     * @param bool|null $paginate Whether to paginate the results
     * @return LengthAwarePaginator|Collection
     */
    public function run(
        ?array $ids = null,
        $role = null,
        ?bool $isDisabled = null,
        ?bool $isCognitoUser = null,
        ?bool $isCognitoUserConfirmed = null,
        ?bool $paginate = true
    ): LengthAwarePaginator|Collection {

        $query = $this->repository->loadQuery(
            ids: $ids,
            role: $role,
            isDisabled: $isDisabled,
            isCognitoUser: $isCognitoUser,
            isCognitoUserConfirmed: $isCognitoUserConfirmed,
        )->addRequestCriteria();
        return $paginate ? $query->paginate() : $query->get();
    }
}
