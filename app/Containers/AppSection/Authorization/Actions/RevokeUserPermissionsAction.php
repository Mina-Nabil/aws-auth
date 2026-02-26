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

final class RevokeUserPermissionsAction extends ParentAction
{
    public function __construct(
        private readonly FindUserByIdTask $findUserByIdTask,
    ) {
    }

    public function run(string $userId, string ...$permissionIds): User
    {
        Gate::authorize(AuthorizationGate::REVOKE_PERMISSIONS, $userId);

        try {
            Log::info('Revoking user permissions: ' . $userId);
            $user = $this->findUserByIdTask->run($userId);
            Log::debug('Permission IDs: ' . implode(', ', $permissionIds));
            foreach ($permissionIds as $permissionId) {
                $user->revokePermissionTo($permissionId);
            }

            return $user;
        } catch (Exception $e) {
            Log::error('Error revoking user permissions: ' . $userId);
            throw new DBCallException("Error revoking user permissions", 0, $e);
        }
    }
}
