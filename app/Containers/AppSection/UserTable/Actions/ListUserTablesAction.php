<?php

namespace App\Containers\AppSection\UserTable\Actions;

use App\Containers\AppSection\UserTable\Enums\UserTableGate;
use App\Containers\AppSection\UserTable\Tasks\ListUserTablesTask;
use App\Ship\Parents\Actions\Action as ParentAction;
use App\Ship\Parents\Exceptions\DBCallException;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

final class ListUserTablesAction extends ParentAction
{
    public function __construct(
        private readonly ListUserTablesTask $listUserTablesTask,
    ) {
    }

    /**
     * List usertables by table_name and logged in user.
     *
     * @param string|null $tableName Optional table name filter
     * @return Collection The collection of usertables
     * @throws DBCallException If there is an error listing the usertables
     */
    public function run(string $tableName): Collection
    {
        Gate::authorize(UserTableGate::LIST_USER_TABLES);

        /** @var \App\Containers\AppSection\User\Models\User $user */
        $user = Auth::user();

        Log::info('Listing usertables for user: ' . $user->id, ['table_name' => $tableName]);

        try {
            return $this->listUserTablesTask->run($user->id, $tableName);
        } catch (Exception $e) {
            Log::error('Error listing usertables: ' . $e->getMessage());
            throw new DBCallException("Error listing usertables", 0, $e);
        }
    }
}
