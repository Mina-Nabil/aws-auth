<?php

namespace App\Containers\AppSection\UserTable\Actions;

use App\Containers\AppSection\UserTable\Data\DTOs\UserTableDTO;
use App\Containers\AppSection\UserTable\Enums\UserTableGate;
use App\Containers\AppSection\UserTable\Models\UserTable;
use App\Containers\AppSection\UserTable\Tasks\CreateOrUpdateUserTableTask;
use App\Ship\Parents\Actions\Action as ParentAction;
use App\Ship\Parents\Exceptions\DBCallException;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

final class CreateOrUpdateUserTableAction extends ParentAction
{
    public function __construct(
        private readonly CreateOrUpdateUserTableTask $createOrUpdateUserTableTask,
    ) {
    }

    /**
     * Create or update usertable for logged in user by table name.
     *
     * @param UserTableDTO $userTableDTO The data for the usertable
     * @return UserTable The created or updated usertable
     * @throws DBCallException If there is an error creating or updating the usertable
     */
    public function run(UserTableDTO $userTableDTO): UserTable
    {
        Gate::authorize(UserTableGate::CREATE_OR_UPDATE_USER_TABLE);

        Log::info('Creating or updating usertable for user: ' . $userTableDTO->user_id, [
            'table_name' => $userTableDTO->table_name,
        ]);

        try {
            return $this->createOrUpdateUserTableTask->run($userTableDTO);
        } catch (Exception $e) {
            Log::error('Error creating or updating usertable: ' . $e->getMessage());
            throw new DBCallException("Error creating or updating usertable", 0, $e);
        }
    }
}
