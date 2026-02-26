<?php

namespace App\Containers\AppSection\Authorization\Tests\Unit\Actions;

use App\Containers\AppSection\Authorization\Actions\ListRolePermissionsAction;
use App\Containers\AppSection\Authorization\Models\Permission;
use App\Containers\AppSection\Authorization\Models\Role;
use App\Containers\AppSection\Authorization\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use App\Containers\AppSection\User\Models\User;

#[CoversClass(ListRolePermissionsAction::class)]
final class ListRolePermissionsActionTest extends UnitTestCase
{
    public function testCanListPermissions(): void
    {
        $this->actingAs(User::factory()->superAdmin()->createOne());

        $role = Role::factory()->createOne()
            ->givePermissionTo(Permission::factory()->count(3)->create());
        $action = app(ListRolePermissionsAction::class);

        $result = $action->run($role->id);

        $this->assertCount(3, $result);
    }
}
