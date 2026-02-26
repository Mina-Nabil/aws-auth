<?php

namespace App\Containers\AppSection\User\Actions;

use App\Containers\AppSection\User\Data\Collections\UserCollection;
use App\Containers\AppSection\User\Enums\UserGate;
use App\Containers\AppSection\User\Tasks\ListUsersTask;
use App\Ship\Parents\Exceptions\DBCallException;
use App\Ship\Parents\Actions\Action as ParentAction;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Access\AuthorizationException;

final class ListUsersAction extends ParentAction
{
    public function __construct(
        private readonly ListUsersTask $listUsersTask,
    ) {
    }

    /**
     * @return LengthAwarePaginator|UserCollection
     * @throws AuthorizationException If the user is not authorized to view all users.
     * @throws DBCallException If there is an error listing the users.
     */
    public function run(?bool $isCognitoUser = null, ?bool $isCognitoUserConfirmed = null): LengthAwarePaginator|UserCollection
    {
        Gate::authorize(UserGate::VIEW_ANY_USER);
        try {
            Log::info('Listing users');
            return $this->listUsersTask->run(isCognitoUser: $isCognitoUser, isCognitoUserConfirmed: $isCognitoUserConfirmed);
        } catch (Exception $e) {
            Log::error('Error listing users: ' . $e->getMessage());
            throw new DBCallException("Error listing users", 0, $e);
        }
    }
}
