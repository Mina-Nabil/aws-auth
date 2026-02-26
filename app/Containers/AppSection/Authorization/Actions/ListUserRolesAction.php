<?php

namespace App\Containers\AppSection\Authorization\Actions;

use App\Containers\AppSection\Authorization\Enums\AuthorizationGate;
use App\Containers\AppSection\Authorization\Data\Collections\RoleCollection;
use App\Containers\AppSection\User\Tasks\FindUserByIdTask;
use App\Ship\Parents\Actions\Action as ParentAction;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Ship\Parents\Exceptions\DBCallException;

final class ListUserRolesAction extends ParentAction
{
    public function __construct(
        private readonly FindUserByIdTask $findUserByIdTask,
    ) {
    }

    public function run(string $id): RoleCollection
    {
        Gate::authorize(AuthorizationGate::VIEW_ROLES, $id);

        try {
            Log::info('Listing user roles: ' . $id);
            return $this->findUserByIdTask->run($id)->roles;
        } catch (Exception $e) {
            Log::error('Error listing user roles: ' . $id);
            throw new DBCallException("Error listing user roles", 0, $e);
        }
    }
}
