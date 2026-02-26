<?php

namespace App\Containers\AppSection\User\Actions;

use Apiato\Core\Repositories\Exceptions\ResourceNotFound;
use App\Containers\AppSection\User\Data\DTOs\UserDTO;
use App\Containers\AppSection\User\Enums\UserGate;
use App\Containers\AppSection\User\Models\User;
use App\Containers\AppSection\User\Tasks\FindUserByIdTask;
use App\Containers\AppSection\User\Tasks\UpdateUserTask;
use App\Ship\Parents\Exceptions\DBCallException;
use App\Ship\Parents\Actions\Action as ParentAction;
use Exception;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Access\AuthorizationException;

final class UpdateStatusAction extends ParentAction
{
    private FindUserByIdTask $findUserByIdTask;

    public function __construct(
        private readonly UpdateUserTask $updateUserTask,
    ) {
    }

    /**
     * @param UserDTO $userDTO The data for the user to update
     * @return User The updated user
     * @throws DBCallException If there is an error updating the user
     * @throws AuthorizationException If the user is not authorized to update the user's status.
     */
    public function run(UserDTO $userDTO): User
    {

        try {
            $this->findUserByIdTask = app(FindUserByIdTask::class);
            $user = $this->findUserByIdTask->run($userDTO->userId);
        } catch (ResourceNotFound $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Error loading user for disabled status change: ' . $user->email);
            throw new DBCallException("Error loading user for disabled status change", 0, $e);
        }

        Gate::authorize(UserGate::UPDATE_USER_STATUS, $user);

        try {
            Log::info('Setting user disabled status: ' . $userDTO->userId . ' to ' . $userDTO->isDisabled);
            return $this->updateUserTask->run($userDTO);
        } catch (ResourceNotFound $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Error setting user disabled status');
            throw new DBCallException("Error setting user status", 0, $e);
        }
    }
}
