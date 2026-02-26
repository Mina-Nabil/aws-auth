<?php

namespace App\Containers\AppSection\Authorization\Tests\Unit\Actions;

use App\Containers\AppSection\Authorization\Actions\ListPermissionsAction;
use App\Containers\AppSection\Authorization\Models\Permission;
use App\Containers\AppSection\Authorization\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use App\Containers\AppSection\User\Models\User;

#[CoversClass(ListPermissionsAction::class)]
final class ListPermissionsActionTest extends UnitTestCase
{
    public function testCanListPermissions(): void
    {

        $this->actingAs(User::factory()->superAdmin()->createOne());

        $action = app(ListPermissionsAction::class);

        $seededPermissionsCount = $action->run(false)->count();

        Permission::factory()->withGuard(auth()->activeGuard())->count(3)->create();

        $result = $action->run(false);

        $this->assertCount($seededPermissionsCount + 3, $result);
    }
}
