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

final class AssignRolesToUserAction extends ParentAction
{
    public function __construct(
        private readonly FindUserByIdTask $findUserByIdTask,
    ) {
    }

    public function run(string $userId, int ...$roleIds): User
    {
        Gate::authorize(AuthorizationGate::ASSIGN_ROLES, $userId);
        try {

            Log::info('Assigning roles to user: ' . $userId);
            Log::debug('Role IDs: ' . implode(', ', $roleIds));
            return $this->findUserByIdTask->run($userId)
            ->assignRole($roleIds);
        } catch (Exception $e) {
            Log::error('Error assigning roles to user: ' . $userId);
            throw new DBCallException("Error assigning roles to user", 0, $e);
        }
    }
}
