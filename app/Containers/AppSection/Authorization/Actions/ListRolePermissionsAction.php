<?php

namespace App\Containers\AppSection\Authorization\Actions;

use App\Containers\AppSection\Authorization\Enums\AuthorizationGate;
use App\Containers\AppSection\Authorization\Data\Collections\PermissionCollection;
use App\Containers\AppSection\Authorization\Tasks\FindRoleTask;
use App\Ship\Parents\Actions\Action as ParentAction;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Ship\Parents\Exceptions\DBCallException;

final class ListRolePermissionsAction extends ParentAction
{
    public function __construct(
        private readonly FindRoleTask $findRoleTask,
    ) {
    }

    public function run(int $id): PermissionCollection
    {
        Gate::authorize(AuthorizationGate::VIEW_PERMISSIONS, $id);

        try {
            Log::info('Listing role permissions: ' . $id);
            return $this->findRoleTask->run($id)->permissions;
        } catch (Exception $e) {
            Log::error('Error listing role permissions: ' . $id);
            throw new DBCallException("Error listing role permissions", 0, $e);
        }
    }
}
