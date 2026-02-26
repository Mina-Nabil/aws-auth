<?php

namespace App\Containers\AppSection\Authorization\Actions;

use App\Containers\AppSection\Authorization\Enums\AuthorizationGate;
use App\Containers\AppSection\Authorization\Models\Role;
use App\Containers\AppSection\Authorization\Tasks\CreateRoleTask;
use App\Ship\Parents\Actions\Action as ParentAction;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Ship\Parents\Exceptions\DBCallException;

final class CreateRoleAction extends ParentAction
{
    public function __construct(
        private readonly CreateRoleTask $createRoleTask,
    ) {
    }

    public function run(string $name, string|null $description = null, string|null $displayName = null): Role
    {
        Gate::authorize(AuthorizationGate::CREATE_ROLES);

        try {
            Log::info('Creating role: ' . $name);
            Log::debug('Parameters', [
                'name' => $name,
                'description' => $description,
                'displayName' => $displayName,
            ]);
            return $this->createRoleTask->run($name, $description, $displayName);
        } catch (Exception $e) {
            Log::error('Error creating role: ' . $name);
            throw new DBCallException("Error creating role", 0, $e);
        }
    }
}
