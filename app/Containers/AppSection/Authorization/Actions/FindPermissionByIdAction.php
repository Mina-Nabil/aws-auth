<?php

namespace App\Containers\AppSection\Authorization\Actions;

use App\Containers\AppSection\Authorization\Enums\AuthorizationGate;
use App\Containers\AppSection\Authorization\Models\Permission;
use App\Containers\AppSection\Authorization\Tasks\FindPermissionByIdTask;
use App\Ship\Parents\Actions\Action as ParentAction;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Ship\Parents\Exceptions\DBCallException;

final class FindPermissionByIdAction extends ParentAction
{
    public function __construct(
        private readonly FindPermissionByIdTask $findPermissionByIdTask,
    ) {
    }

    public function run(string $id): Permission
    {
        Gate::authorize(AuthorizationGate::VIEW_PERMISSIONS, $id);

        try {
            Log::info('Finding permission by ID: ' . $id);
            return $this->findPermissionByIdTask->run($id);
        } catch (Exception $e) {
            Log::error('Error finding permission by ID: ' . $id);
            Log::error($e->getMessage());
            throw new DBCallException("Error finding permission by ID", 0, $e);
        }
    }
}
