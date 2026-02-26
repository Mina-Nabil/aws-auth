<?php

namespace App\Containers\AppSection\File\Policies;

use App\Containers\AppSection\File\Models\File;
use App\Ship\Parents\Models\UserModel as User;
use App\Ship\Parents\Policies\Policy as ParentPolicy;
use Illuminate\Database\Eloquent\Model;

final class FilePolicy extends ParentPolicy
{
    public function create(User $user): bool
    {
        return $user->hasPermissionTo("create-file");
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo("view-any-file");
    }

    public function view(User $user, File $file, ?Model $entity = null): bool
    {
        return $user->hasPermissionTo("download-any-file")
            || ($entity &&
                $file->isLinkedTo($entity) &&
                $user->can("view-" . $entity->getMorphClass()  . "-files", $entity));
    }

    public function update(User $user, File $file = null): bool
    {
        return $user->hasPermissionTo("update-file");
    }

    public function delete(User $user, File $entity): bool
    {
        return $user->hasPermissionTo("delete-file");
    }
}
