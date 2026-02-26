<?php

namespace App\Containers\AppSection\Authorization\Tests\Unit\Actions;

use App\Containers\AppSection\Authorization\Actions\GivePermissionsToRoleAction;
use App\Containers\AppSection\Authorization\Models\Permission;
use App\Containers\AppSection\Authorization\Models\Role;
use App\Containers\AppSection\Authorization\Tests\UnitTestCase;
use App\Containers\AppSection\User\Models\User;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(GivePermissionsToRoleAction::class)]
final class GivePermissionsToRoleActionTest extends UnitTestCase
{
    public function testCanGiveSinglePermission(): void
    {
        $this->actingAs(User::factory()->superAdmin()->createOne([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'superadmin@the.grey',
            'password' => 'youShallNotPass',
        ]));

        $role = Role::factory()->createOne();
        $permission = Permission::factory()->createOne();
        $action = app(GivePermissionsToRoleAction::class);

        $result = $action->run($role->id, $permission->id);

        $this->assertSame($result->id, $role->id);
        $this->assertTrue($result->hasPermissionTo($permission->name));
    }

    public function testCanGiveMultiplePermissions(): void
    {
        $this->actingAs(User::factory()->superAdmin()->createOne([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'superadmin@the.grey',
            'password' => 'youShallNotPass',
        ]));

        $role = Role::factory()->createOne();
        $permissionA = Permission::factory()->createOne();
        $permissionB = Permission::factory()->createOne();
        $action = app(GivePermissionsToRoleAction::class);

        $result = $action->run($role->id, $permissionA->id, $permissionB->id);

        $this->assertSame($result->id, $role->id);
        $this->assertTrue($result->hasAllPermissions([$permissionA->name, $permissionB->name]));
    }
}
