<?php

namespace App\Containers\AppSection\Authorization\Actions;

use App\Containers\AppSection\Authorization\Enums\AuthorizationGate;
use App\Containers\AppSection\Authorization\Data\Collections\PermissionCollection;
use App\Containers\AppSection\User\Tasks\FindUserByIdTask;
use App\Ship\Parents\Actions\Action as ParentAction;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Ship\Parents\Exceptions\DBCallException;

final class ListUserPermissionsAction extends ParentAction
{
    public function __construct(
        private readonly FindUserByIdTask $findUserByIdTask,
    ) {
    }

    public function run(string $id): PermissionCollection
    {
        Gate::authorize(AuthorizationGate::VIEW_PERMISSIONS, $id);

        try {
            Log::info('Listing user permissions: ' . $id);
            return $this->findUserByIdTask->run($id)->permissions;
        } catch (Exception $e) {
            Log::error('Error listing user permissions: ' . $id);
            throw new DBCallException("Error listing user permissions", 0, $e);
        }
    }
}
