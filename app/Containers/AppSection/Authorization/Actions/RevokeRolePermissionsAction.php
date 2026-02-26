<?php

namespace App\Containers\AppSection\Authorization\Actions;

use App\Containers\AppSection\Authorization\Enums\AuthorizationGate;
use App\Containers\AppSection\Authorization\Models\Role;
use App\Containers\AppSection\Authorization\Tasks\FindRoleTask;
use App\Ship\Parents\Actions\Action as ParentAction;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Ship\Parents\Exceptions\DBCallException;

final class RevokeRolePermissionsAction extends ParentAction
{
    public function __construct(
        private readonly FindRoleTask $findRoleTask,
    ) {
    }

    public function run(int $roleId, string ...$permissionIds): Role
    {
        Gate::authorize(AuthorizationGate::REVOKE_PERMISSIONS, $roleId);

        try {
            Log::info('Revoking role permissions: ' . $roleId);
            Log::debug('Permission IDs: ' . implode(', ', $permissionIds));
            $role = $this->findRoleTask->run($roleId);

            foreach ($permissionIds as $permissionId) {
                $role->revokePermissionTo($permissionId);
            }

            return $role;
        } catch (Exception $e) {
            Log::error('Error revoking role permissions: ' . $roleId);
            throw new DBCallException("Error revoking role permissions", 0, $e);
        }
    }
}
