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

final class GivePermissionsToUserAction extends ParentAction
{
    public function __construct(
        private readonly FindUserByIdTask $findUserByIdTask,
    ) {
    }

    public function run(string $userId, string ...$permissionIds): User
    {
        Gate::authorize(AuthorizationGate::GIVE_PERMISSIONS, $userId);

        try {
            Log::info('Giving permissions to user: ' . $userId);
            Log::debug('Permission IDs: ' . implode(', ', $permissionIds));
            return $this->findUserByIdTask->run($userId)
                ->givePermissionTo($permissionIds);
        } catch (Exception $e) {
            Log::error('Error giving permissions to user: ' . $userId);
            throw new DBCallException("Error giving permissions to user", 0, $e);
        }
    }
}
