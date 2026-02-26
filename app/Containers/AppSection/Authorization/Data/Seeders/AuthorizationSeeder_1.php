<?php

namespace App\Containers\AppSection\Authorization\Data\Seeders;

use App\Containers\AppSection\Authorization\Enums\Role as RoleEnum;
use App\Containers\AppSection\Authorization\Models\Role;
use App\Containers\AppSection\Authorization\Tasks\CreateRoleTask;
use App\Ship\Parents\Seeders\Seeder as ParentSeeder;

final class AuthorizationSeeder_1 extends ParentSeeder
{
    public function run(CreateRoleTask $task): void
    {
        foreach (array_keys(config('auth.guards')) as $guardName) {
            foreach (RoleEnum::ROLES_ARRAY as $role) {
                if (Role::where('name', $role->value)->where('guard_name', $guardName)->exists()) {
                    continue;
                }
                $task->run(
                    $role->value,
                    $role->description(),
                    $role->label(),
                    $guardName,
                );
            }
        }
    }
}
