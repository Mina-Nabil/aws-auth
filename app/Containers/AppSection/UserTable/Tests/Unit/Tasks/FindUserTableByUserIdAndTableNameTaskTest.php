<?php

namespace App\Containers\AppSection\UserTable\Tests\Unit\Tasks;

use Apiato\Core\Repositories\Exceptions\ResourceNotFound;
use App\Containers\AppSection\UserTable\Models\UserTable;
use App\Containers\AppSection\UserTable\Tasks\FindUserTableByUserIdAndTableNameTask;
use App\Containers\AppSection\UserTable\Tests\UnitTestCase;
use App\Containers\AppSection\User\Models\User;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FindUserTableByUserIdAndTableNameTask::class)]
final class FindUserTableByUserIdAndTableNameTaskTest extends UnitTestCase
{
    public function testFindUserTableByUserIdAndTableName(): void
    {
        $user = User::factory()->user()->createOne();

        $userTable = UserTable::factory()
            ->forUser($user)
            ->withTableName('jobs')
            ->createOne();

        $task = app(FindUserTableByUserIdAndTableNameTask::class);
        $result = $task->run($user->id, 'jobs');

        $this->assertInstanceOf(UserTable::class, $result);
        $this->assertSame($userTable->id, $result->id);
        $this->assertSame($user->id, $result->user_id);
        $this->assertSame('jobs', $result->table_name);
    }

    public function testFindUserTableReturnsNullWhenNotFound(): void
    {
        $user = User::factory()->user()->createOne();

        $task = app(FindUserTableByUserIdAndTableNameTask::class);
        $result = $task->run($user->id, 'non-existent-table');

        $this->assertNull($result);
    }

    public function testFindUserTableRunOrFailThrowsExceptionWhenNotFound(): void
    {
        $user = User::factory()->user()->createOne();

        $task = app(FindUserTableByUserIdAndTableNameTask::class);

        $this->expectException(ResourceNotFound::class);

        $task->runOrFail($user->id, 'non-existent-table');
    }

    public function testFindUserTableRunOrFailReturnsUserTableWhenFound(): void
    {
        $user = User::factory()->user()->createOne();

        $userTable = UserTable::factory()
            ->forUser($user)
            ->withTableName('jobs')
            ->createOne();

        $task = app(FindUserTableByUserIdAndTableNameTask::class);
        $result = $task->runOrFail($user->id, 'jobs');

        $this->assertInstanceOf(UserTable::class, $result);
        $this->assertSame($userTable->id, $result->id);
    }

    public function testFindUserTableOnlyReturnsSpecifiedUserTable(): void
    {
        $user1 = User::factory()->user()->createOne();
        $user2 = User::factory()->user()->createOne();

        $userTable1 = UserTable::factory()
            ->forUser($user1)
            ->withTableName('jobs')
            ->createOne();

        UserTable::factory()
            ->forUser($user2)
            ->withTableName('jobs')
            ->createOne();

        $task = app(FindUserTableByUserIdAndTableNameTask::class);
        $result = $task->run($user1->id, 'jobs');

        $this->assertInstanceOf(UserTable::class, $result);
        $this->assertSame($userTable1->id, $result->id);
        $this->assertSame($user1->id, $result->user_id);
    }

    public function testFindUserTableWithDifferentTableNames(): void
    {
        $user = User::factory()->user()->createOne();

        $userTable1 = UserTable::factory()
            ->forUser($user)
            ->withTableName('jobs')
            ->createOne();

        $userTable2 = UserTable::factory()
            ->forUser($user)
            ->withTableName('products')
            ->createOne();

        $task = app(FindUserTableByUserIdAndTableNameTask::class);
        $result1 = $task->run($user->id, 'jobs');
        $result2 = $task->run($user->id, 'products');

        $this->assertInstanceOf(UserTable::class, $result1);
        $this->assertInstanceOf(UserTable::class, $result2);
        $this->assertSame($userTable1->id, $result1->id);
        $this->assertSame($userTable2->id, $result2->id);
    }
}
