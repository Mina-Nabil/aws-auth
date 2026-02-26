<?php

namespace App\Containers\AppSection\Authorization\Actions;

use App\Containers\AppSection\Authorization\Data\Collections\PermissionCollection;
use App\Containers\AppSection\Authorization\Enums\AuthorizationGate;
use App\Containers\AppSection\Authorization\Tasks\ListPermissionsTask;
use App\Ship\Parents\Actions\Action as ParentAction;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Ship\Parents\Exceptions\DBCallException;

final class ListPermissionsAction extends ParentAction
{
    public function __construct(
        private readonly ListPermissionsTask $listPermissionsTask,
    ) {
    }

    public function run($paginate = true): LengthAwarePaginator|PermissionCollection
    {
        Gate::authorize(AuthorizationGate::VIEW_ANY_PERMISSIONS);

        try {
            Log::info('Listing permissions');
            return $this->listPermissionsTask->run($paginate, Auth::activeGuard());
        } catch (Exception $e) {
            Log::error('Error listing permissions');
            throw new DBCallException("Error listing permissions", 0, $e);
        }
    }
}
