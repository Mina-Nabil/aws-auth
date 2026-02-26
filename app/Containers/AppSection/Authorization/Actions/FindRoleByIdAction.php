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

final class FindRoleByIdAction extends ParentAction
{
    public function __construct(
        private readonly FindRoleTask $findRoleTask,
    ) {
    }

    public function run(int $id): Role
    {
        Gate::authorize(AuthorizationGate::VIEW_ROLES, $id);

        try {
            Log::info('Finding role by ID: ' . $id);
            return $this->findRoleTask->run($id);
        } catch (Exception $e) {
            Log::error('Error finding role by ID: ' . $id);
            throw new DBCallException("Error finding role by ID", 0, $e);
        }
    }
}
