<?php

namespace App\Containers\AppSection\User\Policies;

use App\Containers\AppSection\User\Models\User;
use App\Ship\Parents\Policies\Policy as ParentPolicy;

final class UserPolicy extends ParentPolicy
{
    public function create(User $user): bool
    {
        return $user->hasPermissionTo("create-user");
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo("view-any-user");
    }

    public function view(User $user, User $entity): bool
    {
        return $user->hasPermissionTo("view-any-user")
            || ($user->hasPermissionTo("view-my-user") && $user->is($entity));
    }

    public function update(User $user, User $entity): bool
    {
        return $user->hasPermissionTo("update-any-user")
            || ($user->hasPermissionTo("update-my-user") && $user->is($entity));
    }

    public function updatePassword(User $user, User $entity): bool
    {
        return $user->hasPermissionTo("update-any-user-password")
            || ($user->hasPermissionTo("update-my-user-password") && $user->is($entity));
    }

    public function updateStatus(User $user, User $entity = null): bool
    {
        return $user->hasPermissionTo("update-user-status");
    }

    public function delete(User $user, User $entity): bool
    {
        return $user->hasPermissionTo("delete-user") && $user->isNot($entity);
    }

    public function listFiles(User $user, User $entity): bool
    {
        return $user->hasPermissionTo('view-any-file') || ($user->hasPermissionTo("list-user-files") && $user->is($entity));
    }

    public function attachAny(User $user): bool
    {
        return $user->hasPermissionTo("attach-any-user-files");
    }

    public function attach(User $user, User $entity): bool
    {
        return $user->hasPermissionTo("attach-any-user-files") || ($user->hasPermissionTo("attach-user-files") && $user->is($entity));
    }

    public function detach(User $user, User $entity): bool
    {
        return $user->hasPermissionTo("detach-any-user-files") || ($user->hasPermissionTo("detach-user-files") && $user->is($entity));
    }

    public function detachAny(User $user): bool
    {
        return $user->hasPermissionTo("detach-any-user-files");
    }

    public function sendTemporaryPassword(User $user, User $entity): bool
    {
        return $user->hasPermissionTo("update-any-user-password") && $user->isNot($entity);
    }
}
