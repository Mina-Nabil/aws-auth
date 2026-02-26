<?php

namespace App\Containers\AppSection\User\Actions;

use App\Containers\AppSection\User\Enums\UserGate;
use App\Ship\Parents\Exceptions\DBCallException;
use App\Containers\AppSection\User\Models\User;
use App\Containers\AppSection\User\Tasks\FindUserByIdTask;
use App\Ship\Parents\Actions\Action as ParentAction;
use Exception;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Access\AuthorizationException;

final class FindUserByIdAction extends ParentAction
{
    public function __construct(
        private readonly FindUserByIdTask $findUserByIdTask,
    ) {
    }

    /**
     * @param string $id The id of the user to find
     * @return User The found user
     * @throws DBCallException If there is an error finding the user
     * @throws AuthorizationException If the user is not authorized to view the user.
     */
    public function run(string $id): User
    {
        try {
            Log::info('Finding user by id: ' . $id);
            $user = $this->findUserByIdTask->run($id);
        } catch (Exception $e) {
            Log::error('Error finding user by id: ' . $id);
            throw new DBCallException("Error finding user by id", 0, $e);
        }
        Gate::authorize(UserGate::VIEW_USER, $user);
        return $user;
    }
}
