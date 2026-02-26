<?php

namespace App\Containers\AppSection\Authorization\Actions;

use App\Containers\AppSection\Authorization\Enums\AuthorizationGate;
use App\Containers\AppSection\User\Models\User;
use App\Containers\AppSection\User\Tasks\FindUserByIdTask;
use App\Ship\Parents\Actions\Action as ParentAction;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Ship\Parents\Exceptions\DBCallException;

final class RemoveUserRolesAction extends ParentAction
{
    public function __construct(
        private readonly FindUserByIdTask $findUserByIdTask,
    ) {
    }

    public function run(string $userId, int ...$roleIds): User
    {
        Gate::authorize(AuthorizationGate::REVOKE_ROLES, $userId);

        try {
            Log::info('Removing user roles: ' . $userId);
            $user = $this->findUserByIdTask->run($userId);
            Log::debug('Role IDs: ' . implode(', ', $roleIds));

            foreach ($roleIds as $roleId) {
                $user->removeRole($roleId);
            }

            return $user;
        } catch (Exception $e) {
            Log::error('Error removing user roles: ' . $userId);
            throw new DBCallException("Error removing user roles", 0, $e);
        }
    }
}
