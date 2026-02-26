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

final class SyncRolePermissionsAction extends ParentAction
{
    public function __construct(
        private readonly FindRoleTask $findRoleTask,
    ) {
    }

    public function run(int $roleId, string ...$permissionIds): Role
    {
        Gate::authorize(AuthorizationGate::SYNC_PERMISSIONS, $roleId);

        try {
            Log::info('Syncing role permissions: ' . $roleId);
            Log::debug('Permission IDs: ' . implode(', ', $permissionIds));
            return $this->findRoleTask->run($roleId)
                ->syncPermissions($permissionIds);
        } catch (Exception $e) {
            Log::error('Error syncing role permissions: ' . $roleId);
            throw new DBCallException("Error syncing role permissions", 0, $e);
        }
    }
}
