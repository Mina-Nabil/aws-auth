<?php

namespace App\Containers\AppSection\Authorization\Tests\Unit\Actions;

use App\Containers\AppSection\Authorization\Actions\FindPermissionByIdAction;
use App\Containers\AppSection\Authorization\Models\Permission;
use App\Containers\AppSection\Authorization\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use App\Containers\AppSection\User\Models\User;

#[CoversClass(FindPermissionByIdAction::class)]
final class FindPermissionByIdActionTest extends UnitTestCase
{
    public function testCanFindPermission(): void
    {
        $this->actingAs(User::factory()->superAdmin()->createOne([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'superadmin@the.grey',
            'password' => 'youShallNotPass',
        ]));

        $permission = Permission::factory()->createOne();

        $result = app(FindPermissionByIdAction::class)->run($permission->id);

        $this->assertTrue($permission->is($result));
    }
}
