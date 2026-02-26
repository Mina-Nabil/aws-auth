<?php

namespace App\Containers\AppSection\Authorization\Tests\Unit\Actions;

use App\Containers\AppSection\Authorization\Actions\SyncRolePermissionsAction;
use App\Containers\AppSection\Authorization\Models\Permission;
use App\Containers\AppSection\Authorization\Models\Role;
use App\Containers\AppSection\Authorization\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use App\Containers\AppSection\User\Models\User;

#[CoversClass(SyncRolePermissionsAction::class)]
final class SyncRolePermissionsActionTest extends UnitTestCase
{
    public function testCanSyncPermission(): void
    {
        $this->actingAs(User::factory()->superAdmin()->createOne());

        $role = Role::factory()->createOne();
        $permissions = Permission::factory()->count(3)->create();
        $role->givePermissionTo($permissions);
        $this->assertCount(3, $role->permissions);
        $action = app(SyncRolePermissionsAction::class);

        $result = $action->run($role->id, $permissions[1]->id);

        $this->assertCount(1, $result->permissions);
        $this->assertSame($permissions[1]->id, $result->permissions->sole()->id);
    }

    public function testCanSyncPermissions(): void
    {
        $this->actingAs(User::factory()->superAdmin()->createOne());

        $role = Role::factory()->createOne();
        $permissions = Permission::factory()->count(3)->create();
        $role->givePermissionTo($permissions);
        $this->assertCount(3, $role->permissions);
        $action = app(SyncRolePermissionsAction::class);

        $result = $action->run($role->id, $permissions[0]->id, $permissions[2]->id);

        $this->assertCount(2, $result->permissions);
        $this->assertContains($permissions[0]->id, $result->permissions->pluck('id'));
        $this->assertContains($permissions[2]->id, $result->permissions->pluck('id'));
    }
}
