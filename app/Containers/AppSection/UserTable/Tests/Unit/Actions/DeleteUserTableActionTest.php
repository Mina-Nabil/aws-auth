<?php

namespace App\Containers\AppSection\UserTable\Tests\Unit\Actions;

use Apiato\Core\Repositories\Exceptions\ResourceNotFound;
use App\Containers\AppSection\UserTable\Actions\DeleteUserTableAction;
use App\Containers\AppSection\UserTable\Models\UserTable;
use App\Containers\AppSection\UserTable\Tests\UnitTestCase;
use App\Containers\AppSection\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DeleteUserTableAction::class)]
final class DeleteUserTableActionTest extends UnitTestCase
{
    public function testCanDeleteUserTable(): void
    {
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        $userTable = UserTable::factory()
            ->forUser($user)
            ->withTableName('jobs')
            ->createOne();

        $action = app(DeleteUserTableAction::class);
        $result = $action->run('jobs');

        $this->assertTrue($result);
        $this->assertDatabaseMissing('user_tables', ['id' => $userTable->id]);
    }

    public function testDeleteUserTableAuthorizationFailure(): void
    {
        $user = User::factory()->user()->createOne();
        // Don't set actingAs to test authorization

        UserTable::factory()
            ->forUser($user)
            ->withTableName('jobs')
            ->createOne();

        $action = app(DeleteUserTableAction::class);

        $this->expectException(AuthorizationException::class);

        $action->run('jobs');
    }

    public function testDeleteNonExistentUserTable(): void
    {
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        $action = app(DeleteUserTableAction::class);

        $this->expectException(ResourceNotFound::class);

        $action->run('non-existent-table');
    }

    public function testDeleteUserTableOnlyDeletesCurrentUserTable(): void
    {
        $user1 = User::factory()->user()->createOne();
        $user2 = User::factory()->user()->createOne();
        $this->actingAs($user1);

        $userTable1 = UserTable::factory()
            ->forUser($user1)
            ->withTableName('jobs')
            ->createOne();

        $userTable2 = UserTable::factory()
            ->forUser($user2)
            ->withTableName('jobs')
            ->createOne();

        $action = app(DeleteUserTableAction::class);
        $action->run('jobs');

        $this->assertDatabaseMissing('user_tables', ['id' => $userTable1->id]);
        $this->assertDatabaseHas('user_tables', ['id' => $userTable2->id]);
    }

    public function testCanDeleteUserTableWithSuperAdminRole(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $userTable = UserTable::factory()
            ->forUser($user)
            ->withTableName('jobs')
            ->createOne();

        $action = app(DeleteUserTableAction::class);
        $result = $action->run('jobs');

        $this->assertTrue($result);
        $this->assertDatabaseMissing('user_tables', ['id' => $userTable->id]);
    }

    public function testCanDeleteUserTableWithAdminRole(): void
    {
        $user = User::factory()->admin()->createOne();
        $this->actingAs($user);

        $userTable = UserTable::factory()
            ->forUser($user)
            ->withTableName('jobs')
            ->createOne();

        $action = app(DeleteUserTableAction::class);
        $result = $action->run('jobs');

        $this->assertTrue($result);
        $this->assertDatabaseMissing('user_tables', ['id' => $userTable->id]);
    }


}
