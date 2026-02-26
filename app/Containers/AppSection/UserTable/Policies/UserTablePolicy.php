<?php

namespace App\Containers\AppSection\UserTable\Policies;

use App\Containers\AppSection\UserTable\Models\UserTable;
use App\Ship\Parents\Models\UserModel as User;
use App\Ship\Parents\Policies\Policy as ParentPolicy;

final class UserTablePolicy extends ParentPolicy
{
    /**
     * Determine whether the user can list usertables.
     */
    public function list(User $user): bool
    {
        return $user->hasPermissionTo('list-usertables');
    }

    /**
     * Determine whether the user can create or update usertables.
     */
    public function createOrUpdate(User $user): bool
    {
        return $user->hasPermissionTo('create-or-update-usertable');
    }

    /**
     * Determine whether the user can delete usertables.
     */
    public function delete(User $user): bool
    {
        return $user->hasPermissionTo('delete-usertable');
    }
}
