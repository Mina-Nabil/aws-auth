<?php

namespace App\Containers\AppSection\Authorization\Actions;

use App\Containers\AppSection\Authorization\Data\Collections\RoleCollection;
use App\Containers\AppSection\Authorization\Enums\AuthorizationGate;
use App\Containers\AppSection\Authorization\Tasks\ListRolesTask;
use App\Ship\Parents\Actions\Action as ParentAction;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Ship\Parents\Exceptions\DBCallException;

final class ListRolesAction extends ParentAction
{
    public function __construct(
        private readonly ListRolesTask $listRolesTask,
    ) {
    }

    public function run(): LengthAwarePaginator|RoleCollection
    {
        Gate::authorize(AuthorizationGate::VIEW_ANY_ROLES);

        try {
            Log::info('Listing roles');
            return $this->listRolesTask->run(Auth::activeGuard());
        } catch (Exception $e) {
            Log::error('Error listing roles');
            throw new DBCallException("Error listing roles", 0, $e);
        }
    }
}
