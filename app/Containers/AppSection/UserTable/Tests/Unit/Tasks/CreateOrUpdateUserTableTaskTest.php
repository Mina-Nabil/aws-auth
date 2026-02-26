<?php

namespace App\Containers\AppSection\UserTable\Tests\Unit\Tasks;

use App\Containers\AppSection\UserTable\Data\DTOs\UserTableDTO;
use App\Containers\AppSection\UserTable\Models\UserTable;
use App\Containers\AppSection\UserTable\Tasks\CreateOrUpdateUserTableTask;
use App\Containers\AppSection\UserTable\Tests\UnitTestCase;
use App\Containers\AppSection\User\Models\User;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CreateOrUpdateUserTableTask::class)]
final class CreateOrUpdateUserTableTaskTest extends UnitTestCase
{
    public function testCreateUserTable(): void
    {
        $user = User::factory()->user()->createOne();

        $userTableDTO = UserTableDTO::fromArray([
            'user_id' => $user->id,
            'table_name' => 'jobs',
            'columns' => ['job_number', 'customer', 'status'],
        ]);

        $task = app(CreateOrUpdateUserTableTask::class);
        $result = $task->run($userTableDTO);

        $this->assertInstanceOf(UserTable::class, $result);
        $this->assertSame($user->id, $result->user_id);
        $this->assertSame('jobs', $result->table_name);
        $this->assertSame(['job_number', 'customer', 'status'], $result->columns);
    }

    public function testUpdateExistingUserTable(): void
    {
        $user = User::factory()->user()->createOne();

        $existingUserTable = UserTable::factory()
            ->forUser($user)
            ->withTableName('jobs')
            ->withColumns(['old_column'])
            ->createOne();

        $userTableDTO = UserTableDTO::fromArray([
            'user_id' => $user->id,
            'table_name' => 'jobs',
            'columns' => ['job_number', 'status', 'engineer'],
        ]);

        $task = app(CreateOrUpdateUserTableTask::class);
        $result = $task->run($userTableDTO);

        $this->assertInstanceOf(UserTable::class, $result);
        $this->assertSame($existingUserTable->id, $result->id);
        $this->assertSame(['job_number', 'status', 'engineer'], $result->columns);
    }

    public function testCreateUserTableWithEmptyColumns(): void
    {
        $user = User::factory()->user()->createOne();

        $userTableDTO = UserTableDTO::fromArray([
            'user_id' => $user->id,
            'table_name' => 'jobs',
            'columns' => [],
        ]);

        $task = app(CreateOrUpdateUserTableTask::class);
        $result = $task->run($userTableDTO);

        $this->assertInstanceOf(UserTable::class, $result);
        $this->assertSame([], $result->columns);
    }

    public function testUpdateUserTableWithNullColumnsUsesEmptyArray(): void
    {
        $user = User::factory()->user()->createOne();

        $existingUserTable = UserTable::factory()
            ->forUser($user)
            ->withTableName('jobs')
            ->withColumns(['old_column'])
            ->createOne();

        $userTableDTO = UserTableDTO::fromArray([
            'user_id' => $user->id,
            'table_name' => 'jobs',
            'columns' => null,
        ]);

        $task = app(CreateOrUpdateUserTableTask::class);
        $result = $task->run($userTableDTO);

        $this->assertSame($existingUserTable->id, $result->id);
        $this->assertSame([], $result->columns);
    }

    public function testCreateUserTableWithMultipleColumns(): void
    {
        $user = User::factory()->user()->createOne();

        $columns = ['col1', 'col2', 'col3', 'col4', 'col5'];
        $userTableDTO = UserTableDTO::fromArray([
            'user_id' => $user->id,
            'table_name' => 'jobs',
            'columns' => $columns,
        ]);

        $task = app(CreateOrUpdateUserTableTask::class);
        $result = $task->run($userTableDTO);

        $this->assertInstanceOf(UserTable::class, $result);
        $this->assertSame($columns, $result->columns);
    }

    public function testCreateUserTableWithUniqueConstraint(): void
    {
        $user = User::factory()->user()->createOne();

        // Create first table
        $userTableDTO1 = UserTableDTO::fromArray([
            'user_id' => $user->id,
            'table_name' => 'jobs',
            'columns' => ['col1'],
        ]);

        $task = app(CreateOrUpdateUserTableTask::class);
        $result1 = $task->run($userTableDTO1);

        // Try to create another with same user_id and table_name - should update
        $userTableDTO2 = UserTableDTO::fromArray([
            'user_id' => $user->id,
            'table_name' => 'jobs',
            'columns' => ['col2'],
        ]);

        $result2 = $task->run($userTableDTO2);

        $this->assertSame($result1->id, $result2->id);
        $this->assertSame(['col2'], $result2->columns);
        $this->assertDatabaseCount('user_tables', 1);
    }

    public function testCreateUserTableForDifferentUsers(): void
    {
        $user1 = User::factory()->user()->createOne();
        $user2 = User::factory()->user()->createOne();

        $userTableDTO1 = UserTableDTO::fromArray([
            'user_id' => $user1->id,
            'table_name' => 'jobs',
            'columns' => ['col1'],
        ]);

        $userTableDTO2 = UserTableDTO::fromArray([
            'user_id' => $user2->id,
            'table_name' => 'jobs',
            'columns' => ['col2'],
        ]);

        $task = app(CreateOrUpdateUserTableTask::class);
        $result1 = $task->run($userTableDTO1);
        $result2 = $task->run($userTableDTO2);

        $this->assertNotSame($result1->id, $result2->id);
        $this->assertSame($user1->id, $result1->user_id);
        $this->assertSame($user2->id, $result2->user_id);
        $this->assertDatabaseCount('user_tables', 2);
    }
}
