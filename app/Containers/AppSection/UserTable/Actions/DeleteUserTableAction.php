<?php

namespace App\Containers\AppSection\UserTable\Actions;

use Apiato\Core\Repositories\Exceptions\ResourceNotFound;
use App\Containers\AppSection\UserTable\Enums\UserTableGate;
use App\Containers\AppSection\UserTable\Tasks\DeleteUserTableTask;
use App\Ship\Parents\Actions\Action as ParentAction;
use App\Ship\Parents\Exceptions\DBCallException;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

final class DeleteUserTableAction extends ParentAction
{
    public function __construct(
        private readonly DeleteUserTableTask $deleteUserTableTask,
    ) {
    }

    /**
     * Delete usertable by table_name and logged in user.
     *
     * @param string $tableName The table name to delete
     * @return bool
     * @throws DBCallException If there is an error deleting the usertable
     */
    public function run(string $tableName): bool
    {
        Gate::authorize(UserTableGate::DELETE_USER_TABLE);

        /** @var \App\Containers\AppSection\User\Models\User $user */
        $user = Auth::user();

        Log::info('Deleting usertable for user: ' . $user->id, ['table_name' => $tableName]);

        try {
            return $this->deleteUserTableTask->run($user->id, $tableName);
        } catch (ResourceNotFound $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Error deleting usertable: ' . $e->getMessage());
            throw new DBCallException("Error deleting usertable", 0, $e);
        }
    }
}
