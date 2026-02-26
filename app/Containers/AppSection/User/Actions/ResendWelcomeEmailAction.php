<?php

namespace App\Containers\AppSection\User\Actions;

use App\Containers\AppSection\User\Data\DTOs\UserDTO;
use App\Containers\AppSection\User\Enums\UserGate;
use App\Containers\AppSection\User\Models\User;
use App\Containers\AppSection\User\Tasks\FindUserByIdTask;
use App\Containers\AppSection\User\Tasks\GenerateTemporaryPasswordTask;
use App\Containers\AppSection\User\Tasks\SendWelcomeEmailTask;
use App\Containers\AppSection\User\Tasks\UpdateUserTask;
use App\Ship\Parents\Actions\Action as ParentAction;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

final class ResendWelcomeEmailAction extends ParentAction
{
    public function __construct(
        private readonly FindUserByIdTask $findUserByIdTask,
        private readonly GenerateTemporaryPasswordTask $generateTemporaryPasswordTask,
        private readonly UpdateUserTask $updateUserTask,
        private readonly SendWelcomeEmailTask $sendWelcomeEmailTask,
    ) {
    }

    public function run(UserDTO $userDTO): User
    {
        $user = $this->findUserByIdTask->run($userDTO->userId);

        if (!Gate::check(UserGate::SEND_TEMPORARY_PASSWORD, $user)) {
            throw new AuthorizationException('You are not authorized');
        }

        $temporaryPassword = $this->generateTemporaryPasswordTask->run();
        $userDTO = $userDTO->withTemporaryPassword($temporaryPassword);

        $this->updateUserTask->run($userDTO);
        $this->sendWelcomeEmailTask->run($user, $temporaryPassword);

        return $user;
    }
}
