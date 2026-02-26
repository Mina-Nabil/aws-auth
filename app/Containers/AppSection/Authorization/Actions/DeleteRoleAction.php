<?php

namespace App\Containers\AppSection\Authorization\Actions;

use App\Containers\AppSection\Authorization\Enums\AuthorizationGate;
use App\Containers\AppSection\Authorization\Tasks\DeleteRoleTask;
use App\Ship\Parents\Actions\Action as ParentAction;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Ship\Parents\Exceptions\DBCallException;

final class DeleteRoleAction extends ParentAction
{
    public function __construct(
        private readonly DeleteRoleTask $deleteRoleTask,
    ) {
    }

    public function run(int $id): bool
    {
        Gate::authorize(AuthorizationGate::DELETE_ROLES);

        try {
            Log::info('Deleting role: ' . $id);
            return $this->deleteRoleTask->run($id);
        } catch (Exception $e) {
            Log::error('Error deleting role: ' . $id);
            throw new DBCallException("Error deleting role", 0, $e);
        }
    }
}
