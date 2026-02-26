<?php

namespace App\Containers\AppSection\Authorization\Tests\Unit\Actions;

use App\Containers\AppSection\Authorization\Actions\GivePermissionsToUserAction;
use App\Containers\AppSection\Authorization\Models\Permission;
use App\Containers\AppSection\Authorization\Tests\UnitTestCase;
use App\Containers\AppSection\User\Models\User;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(GivePermissionsToUserAction::class)]
final class GivePermissionsToUserActionTest extends UnitTestCase
{
    public function testCanGiveSinglePermission(): void
    {
        $this->actingAs(User::factory()->superAdmin()->createOne([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'superadmin@the.grey',
            'password' => 'youShallNotPass',
        ]));

        $user = User::factory()->createOne();
        $permission = Permission::factory()->createOne();
        $action = app(GivePermissionsToUserAction::class);

        $result = $action->run($user->id, $permission->id);

        $this->assertSame($result->id, $user->id);
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

        $user = User::factory()->createOne();
        $permissionA = Permission::factory()->createOne();
        $permissionB = Permission::factory()->createOne();
        $action = app(GivePermissionsToUserAction::class);

        $result = $action->run($user->id, $permissionA->id, $permissionB->id);

        $this->assertSame($result->id, $user->id);
        $this->assertTrue($result->hasAllPermissions([$permissionA->name, $permissionB->name]));
    }
}
