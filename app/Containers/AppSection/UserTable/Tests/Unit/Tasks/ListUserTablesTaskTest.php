<?php

namespace App\Containers\AppSection\UserTable\Tests\Unit\Tasks;

use App\Containers\AppSection\UserTable\Models\UserTable;
use App\Containers\AppSection\UserTable\Tasks\ListUserTablesTask;
use App\Containers\AppSection\UserTable\Tests\UnitTestCase;
use App\Containers\AppSection\User\Models\User;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ListUserTablesTask::class)]
final class ListUserTablesTaskTest extends UnitTestCase
{
    public function testListUserTablesByUserId(): void
    {
        $user = User::factory()->user()->createOne();

        UserTable::factory()
            ->forUser($user)
            ->withTableName('jobs')
            ->createOne();

        $task = app(ListUserTablesTask::class);
        $result = $task->run($user->id, 'jobs');

        $this->assertCount(1, $result);
        $this->assertContainsOnlyInstancesOf(UserTable::class, $result);
    }

    public function testListUserTablesReturnsEmptyWhenNoTables(): void
    {
        $user = User::factory()->user()->createOne();

        $task = app(ListUserTablesTask::class);
        $result = $task->run($user->id, 'jobs');

        $this->assertCount(0, $result);
    }

    public function testListUserTablesFiltersByTableName(): void
    {
        $user = User::factory()->user()->createOne();

        UserTable::factory()
            ->forUser($user)
            ->withTableName('jobs')
            ->createOne();

        UserTable::factory()
            ->forUser($user)
            ->withTableName('products')
            ->createOne();

        $task = app(ListUserTablesTask::class);
        $result = $task->run($user->id, 'jobs');

        $this->assertCount(1, $result);
        $this->assertSame('jobs', $result->first()->table_name);
    }

    public function testListUserTablesOnlyReturnsSpecifiedUserTables(): void
    {
        $user1 = User::factory()->user()->createOne();
        $user2 = User::factory()->user()->createOne();

        UserTable::factory()
            ->forUser($user1)
            ->withTableName('jobs')
            ->createOne();

        UserTable::factory()
            ->forUser($user2)
            ->withTableName('jobs')
            ->createOne();

        $task = app(ListUserTablesTask::class);
        $result = $task->run($user1->id, 'jobs');

        $this->assertCount(1, $result);
        $this->assertSame($user1->id, $result->first()->user_id);
    }

    public function testListUserTablesWithNullTableName(): void
    {
        $user = User::factory()->user()->createOne();

        UserTable::factory()
            ->forUser($user)
            ->withTableName('jobs')
            ->createOne();

        UserTable::factory()
            ->forUser($user)
            ->withTableName('products')
            ->createOne();

        $task = app(ListUserTablesTask::class);
        $result = $task->run($user->id, null);

        $this->assertCount(2, $result);
    }
}
