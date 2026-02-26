<?php

namespace App\Containers\AppSection\Authorization\Tests\Unit\Actions;

use App\Containers\AppSection\Authorization\Actions\ListRolesAction;
use App\Containers\AppSection\Authorization\Models\Role;
use App\Containers\AppSection\Authorization\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use App\Containers\AppSection\User\Models\User;

#[CoversClass(ListRolesAction::class)]
final class ListRolesActionTest extends UnitTestCase
{
    public function testCanListRoles(): void
    {
        $action = app(ListRolesAction::class);
        $this->actingAs(User::factory()->superAdmin()->createOne());

        $seededRolesCount = $action->run()->count();
        Role::factory()->count(2)->create();

        $result = $action->run();

        $this->assertCount($seededRolesCount + 2, $result);
    }
}
