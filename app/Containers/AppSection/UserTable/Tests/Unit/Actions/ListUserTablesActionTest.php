<?php

namespace App\Containers\AppSection\UserTable\Tests\Unit\Actions;

use App\Containers\AppSection\UserTable\Actions\ListUserTablesAction;
use App\Containers\AppSection\UserTable\Models\UserTable;
use App\Containers\AppSection\UserTable\Tests\UnitTestCase;
use App\Containers\AppSection\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ListUserTablesAction::class)]
final class ListUserTablesActionTest extends UnitTestCase
{
    public function testCanListUserTables(): void
    {
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        UserTable::factory()
            ->forUser($user)
            ->withTableName('jobs')
            ->createOne();

        $action = app(ListUserTablesAction::class);
        $result = $action->run('jobs');

        $this->assertCount(1, $result);
        $this->assertContainsOnlyInstancesOf(UserTable::class, $result);
    }

    public function testListUserTablesReturnsEmptyWhenNoTables(): void
    {
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        $action = app(ListUserTablesAction::class);
        $result = $action->run('jobs');

        $this->assertCount(0, $result);
    }

    public function testListUserTablesFiltersByTableName(): void
    {
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        UserTable::factory()
            ->forUser($user)
            ->withTableName('jobs')
            ->createOne();

        UserTable::factory()
            ->forUser($user)
            ->withTableName('products')
            ->createOne();

        $action = app(ListUserTablesAction::class);
        $result = $action->run('jobs');

        $this->assertCount(1, $result);
        $this->assertSame('jobs', $result->first()->table_name);
    }

    public function testListUserTablesOnlyReturnsCurrentUserTables(): void
    {
        $user1 = User::factory()->user()->createOne();
        $user2 = User::factory()->user()->createOne();
        $this->actingAs($user1);

        UserTable::factory()
            ->forUser($user1)
            ->withTableName('jobs')
            ->createOne();

        UserTable::factory()
            ->forUser($user2)
            ->withTableName('jobs')
            ->createOne();

        $action = app(ListUserTablesAction::class);
        $result = $action->run('jobs');

        $this->assertCount(1, $result);
        $this->assertSame($user1->id, $result->first()->user_id);
    }

    public function testListUserTablesAuthorizationFailure(): void
    {
        $this->expectException(AuthorizationException::class);

        $action = app(ListUserTablesAction::class);
        $action->run('jobs');
    }

    public function testCanListUserTablesWithSuperAdminRole(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        UserTable::factory()
            ->forUser($user)
            ->withTableName('jobs')
            ->createOne();

        $action = app(ListUserTablesAction::class);
        $result = $action->run('jobs');

        $this->assertCount(1, $result);
    }

    public function testCanListUserTablesWithAdminRole(): void
    {
        $user = User::factory()->admin()->createOne();
        $this->actingAs($user);

        UserTable::factory()
            ->forUser($user)
            ->withTableName('jobs')
            ->createOne();

        $action = app(ListUserTablesAction::class);
        $result = $action->run('jobs');

        $this->assertCount(1, $result);
    }

}
