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

final class GivePermissionsToRoleAction extends ParentAction
{
    public function __construct(
        private readonly FindRoleTask $findRoleTask,
    ) {
    }

    public function run(int $roleId, string ...$permissionIds): Role
    {
        Gate::authorize(AuthorizationGate::GIVE_PERMISSIONS, $roleId);

        try {
            Log::info('Giving permissions to role: ' . $roleId);
            Log::debug('Permission IDs: ' . implode(', ', $permissionIds));
            return $this->findRoleTask->run($roleId)
                ->givePermissionTo($permissionIds);
        } catch (Exception $e) {
            Log::error('Error giving permissions to role: ' . $roleId);
            throw new DBCallException("Error giving permissions to role", 0, $e);
        }
    }
}
