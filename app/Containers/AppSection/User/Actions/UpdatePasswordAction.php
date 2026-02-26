<?php

namespace App\Containers\AppSection\User\Actions;

use Apiato\Core\Repositories\Exceptions\ResourceNotFound;
use App\Containers\AppSection\User\Data\DTOs\UserDTO;
use App\Containers\AppSection\User\Enums\UserGate;
use App\Ship\Parents\Exceptions\DBCallException;
use App\Containers\AppSection\User\Models\User;
use App\Containers\AppSection\User\Tasks\FindUserByIdTask;
use App\Containers\AppSection\User\Tasks\UpdateUserTask;
use App\Ship\Notifications\InfoNotification;
use App\Ship\Parents\Actions\Action as ParentAction;
use Exception;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Access\AuthorizationException;

final class UpdatePasswordAction extends ParentAction
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
     * @throws AuthorizationException If the user is not authorized to update the user's password.
     */
    public function run(UserDTO $userDTO): User
    {
        try {
            $this->findUserByIdTask = app(FindUserByIdTask::class);
            $user = $this->findUserByIdTask->run($userDTO->userId);
        } catch (ResourceNotFound $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Error loading user for password change: ' . $user->email);
            throw new DBCallException("Error loading user for password change", 0, $e);
        }

        Gate::authorize(UserGate::UPDATE_USER_PASSWORD, $user);

        try {
            Log::info('Updating password for user: ' . $user->email);
            $user = $this->updateUserTask->run($userDTO);
            $user->notify(new InfoNotification("Password Updated", "Your password has been updated."));
        } catch (Exception $e) {
            Log::error('Error updating password: ' . $user->email);
            Log::error($e->getMessage());
            throw new DBCallException("Error updating password", 0, $e);
        }



        return $user;
    }
}
