<?php

namespace App\Containers\AppSection\User\Tests\Unit\Actions;

use App\Containers\AppSection\User\Actions\ListUsersAction;
use App\Containers\AppSection\User\Models\User;
use App\Containers\AppSection\User\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ListUsersAction::class)]
final class ListUsersActionTest extends UnitTestCase
{
    public function testCanListUsers(): void
    {
        $this->actingAs(User::factory()->superAdmin()->createOne([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'superadmin@the.grey',
            'password' => 'youShallNotPass',
        ]));
        User::factory()->count(4)->create();
        $task = app(ListUsersAction::class);

        $foundUsers = $task->run();

        $this->assertCount(6, $foundUsers); //adding seeder admin user
    }
}
