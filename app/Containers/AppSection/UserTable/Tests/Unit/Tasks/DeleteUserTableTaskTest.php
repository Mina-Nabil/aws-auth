<?php

namespace App\Containers\AppSection\UserTable\Tests\Unit\Tasks;

use Apiato\Core\Repositories\Exceptions\ResourceNotFound;
use App\Containers\AppSection\UserTable\Models\UserTable;
use App\Containers\AppSection\UserTable\Tasks\DeleteUserTableTask;
use App\Containers\AppSection\UserTable\Tests\UnitTestCase;
use App\Containers\AppSection\User\Models\User;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DeleteUserTableTask::class)]
final class DeleteUserTableTaskTest extends UnitTestCase
{
    public function testDeleteUserTable(): void
    {
        $user = User::factory()->user()->createOne();

        $userTable = UserTable::factory()
            ->forUser($user)
            ->withTableName('jobs')
            ->createOne();

        $task = app(DeleteUserTableTask::class);
        $result = $task->run($user->id, 'jobs');

        $this->assertTrue($result);
        $this->assertDatabaseMissing('user_tables', ['id' => $userTable->id]);
    }

    public function testDeleteNonExistentUserTable(): void
    {
        $user = User::factory()->user()->createOne();

        $task = app(DeleteUserTableTask::class);

        $this->expectException(ResourceNotFound::class);

        $task->run($user->id, 'non-existent-table');
    }

    public function testDeleteUserTableOnlyDeletesSpecifiedUserTable(): void
    {
        $user1 = User::factory()->user()->createOne();
        $user2 = User::factory()->user()->createOne();

        $userTable1 = UserTable::factory()
            ->forUser($user1)
            ->withTableName('jobs')
            ->createOne();

        $userTable2 = UserTable::factory()
            ->forUser($user2)
            ->withTableName('jobs')
            ->createOne();

        $task = app(DeleteUserTableTask::class);
        $task->run($user1->id, 'jobs');

        $this->assertDatabaseMissing('user_tables', ['id' => $userTable1->id]);
        $this->assertDatabaseHas('user_tables', ['id' => $userTable2->id]);
    }

    public function testDeleteUserTableWithDifferentTableNames(): void
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

        $task = app(DeleteUserTableTask::class);
        $task->run($user->id, 'jobs');

        $this->assertDatabaseMissing('user_tables', ['id' => $userTable1->id]);
        $this->assertDatabaseHas('user_tables', ['id' => $userTable2->id]);
    }
}
