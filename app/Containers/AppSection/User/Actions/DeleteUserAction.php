<?php

namespace App\Containers\AppSection\User\Actions;

use App\Containers\AppSection\User\Enums\UserGate;
use App\Containers\AppSection\User\Tasks\DeleteUserTask;
use App\Containers\AppSection\User\Tasks\FindUserByIdTask;
use App\Containers\AppSection\User\Services\CognitoSyncClient;
use App\Ship\Parents\Exceptions\DBCallException;
use App\Ship\Parents\Actions\Action as ParentAction;
use App\Ship\Parents\Exceptions\InvalidRequestException;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

final class DeleteUserAction extends ParentAction
{
    public function __construct(
        private readonly FindUserByIdTask $findUserByIdTask,
        private readonly DeleteUserTask $deleteUserTask,
        private readonly CognitoSyncClient $cognitoSyncClient,
    ) {
    }

    /**
     * @param string $id The id of the user to delete
     * @return bool
     * @throws InvalidRequestException
     * @throws DBCallException
     * @throws AuthorizationException
     */
    public function run(string $id): bool
    {

        $user = $this->findUserByIdTask->run($id);
        Gate::authorize(UserGate::DELETE_USER, $user);

        /**
         * @var User $loggedInUser
         */
        $loggedInUser = Auth::user();

        if ($loggedInUser->is($user)) {
            throw new InvalidRequestException("You cannot delete yourself");
        }

        try {
            Log::info('Deleting user: ' . $user->email);
            DB::transaction(function () use ($user, $id) {
                $this->cognitoSyncClient->deleteUser($user->email);
                $this->deleteUserTask->run($id);
            });
            return true;
        } catch (Exception $e) {
            Log::error('Error deleting user: ' . $e->getMessage());
            throw new DBCallException("Error deleting user", 0, $e);
        }
    }
}
