<?php

namespace App\Containers\AppSection\User\Actions;

use App\Containers\AppSection\User\Enums\UserGate;
use App\Containers\AppSection\User\Models\User;
use App\Containers\AppSection\User\Tasks\FindUserByIdTask;
use App\Containers\AppSection\User\Tasks\UpdateUserTask;
use App\Ship\Parents\Actions\Action as ParentAction;
use Illuminate\Support\Facades\Gate;
use Apiato\Core\Repositories\Exceptions\ResourceNotFound;
use App\Containers\AppSection\Authorization\Actions\SyncUserRolesAction;
use App\Containers\AppSection\Authorization\Enums\AuthorizationGate;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Ship\Parents\Exceptions\DBCallException;
use App\Containers\AppSection\User\Data\DTOs\UserDTO;
use App\Containers\AppSection\Authorization\Tasks\FindRoleTask;
use App\Containers\AppSection\User\Services\CognitoSyncClient;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final class UpdateUserAction extends ParentAction
{
    private FindUserByIdTask $findUserByIdTask;

    public function __construct(
        private readonly UpdateUserTask $updateUserTask,
        private readonly SyncUserRolesAction $syncUserRolesAction,
        private readonly FindRoleTask $findRoleTask,
        private readonly CognitoSyncClient $cognitoSyncClient,
    ) {}

    /**
     * @param UserDTO $userDTO The data for the user to update
     * @return User The updated user
     * @throws DBCallException If there is an error updating the user
     * @throws AuthorizationException If the user is not authorized to update the user.
     * @throws ResourceNotFound If the user is not found.
     */
    public function run(UserDTO $userDTO): User
    {
        try {

            $this->findUserByIdTask = app(FindUserByIdTask::class);
            $user = $this->findUserByIdTask->run($userDTO->userId);
        } catch (ResourceNotFound $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Error loading user for update: ' . $userDTO->userId);
            throw new DBCallException("Error loading user for update", 0, $e);
        }

        Gate::authorize(UserGate::UPDATE_USER, $user);

        try {
            DB::beginTransaction();
            Log::info('Updating user: ' . $userDTO->userId);
            $user = $this->updateUserTask->run($userDTO);
            if ($userDTO->role) {
                Gate::authorize(AuthorizationGate::SYNC_ROLES, $user->id);
                $role = $this->findRoleTask->run($userDTO->role);
                $this->syncUserRolesAction->run($user->id, $role->id);
                if (config('cognito-auth.sync_user_pool_active')) {
                    $this->cognitoSyncClient->setUserAttributes($user->email, [
                            'first_name' => $user->first_name,
                            'last_name' => $user->last_name,
                        ]);
                }
            }
            DB::commit();
            return $user;
        } catch (AuthorizationException $e) {
            DB::rollBack();
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating user: ' . $userDTO->userId);
            throw new DBCallException("Error updating user", 0, $e);
        }
    }
}
