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

final class SyncUserRolesAction extends ParentAction
{
    public function __construct(
        private readonly FindUserByIdTask $findUserByIdTask,
    ) {
    }

    public function run(string $userId, int ...$roleIds): User
    {
        Gate::authorize(AuthorizationGate::SYNC_ROLES, $userId);

        try {
            Log::info('Syncing user roles: ' . $userId);
            Log::debug('Role IDs: ' . implode(', ', $roleIds));
            return $this->findUserByIdTask->run($userId)
                ->syncRoles($roleIds);
        } catch (Exception $e) {
            Log::error('Error syncing user roles: ' . $userId);
            throw new DBCallException("Error syncing user roles", 0, $e);
        }
    }
}
