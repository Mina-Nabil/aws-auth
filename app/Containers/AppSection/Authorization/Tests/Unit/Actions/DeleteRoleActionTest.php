<?php

namespace App\Containers\AppSection\Authorization\Tests\Unit\Actions;

use App\Containers\AppSection\Authorization\Actions\DeleteRoleAction;
use App\Containers\AppSection\Authorization\Models\Role;
use App\Containers\AppSection\Authorization\Tests\UnitTestCase;
use App\Containers\AppSection\User\Models\User;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DeleteRoleAction::class)]
final class DeleteRoleActionTest extends UnitTestCase
{
    public function testCanDeleteRole(): void
    {
        $this->actingAs(User::factory()->superAdmin()->createOne([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'superadmin@the.grey',
            'password' => 'youShallNotPass',
        ]));

        $role = Role::factory()->createOne();
        $action = app(DeleteRoleAction::class);
        $this->assertModelExists($role);

        $result = $action->run($role->id);

        $this->assertTrue($result);
        $this->assertModelMissing($role);
    }
}
