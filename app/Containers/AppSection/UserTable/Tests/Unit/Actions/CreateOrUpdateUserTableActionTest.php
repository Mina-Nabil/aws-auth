<?php

namespace App\Containers\AppSection\UserTable\Tests\Unit\Actions;

use App\Containers\AppSection\UserTable\Actions\CreateOrUpdateUserTableAction;
use App\Containers\AppSection\UserTable\Data\DTOs\UserTableDTO;
use App\Containers\AppSection\UserTable\Models\UserTable;
use App\Containers\AppSection\UserTable\Tests\UnitTestCase;
use App\Containers\AppSection\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CreateOrUpdateUserTableAction::class)]
final class CreateOrUpdateUserTableActionTest extends UnitTestCase
{
    public function testCanCreateUserTable(): void
    {
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        $userTableDTO = UserTableDTO::fromArray([
            'user_id' => $user->id,
            'table_name' => 'jobs',
            'columns' => ['job_number', 'customer', 'status'],
        ]);

        $action = app(CreateOrUpdateUserTableAction::class);
        $result = $action->run($userTableDTO);

        $this->assertInstanceOf(UserTable::class, $result);
        $this->assertSame($user->id, $result->user_id);
        $this->assertSame('jobs', $result->table_name);
        $this->assertSame(['job_number', 'customer', 'status'], $result->columns);
    }

    public function testCanUpdateExistingUserTable(): void
    {
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

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

        $action = app(CreateOrUpdateUserTableAction::class);
        $result = $action->run($userTableDTO);

        $this->assertInstanceOf(UserTable::class, $result);
        $this->assertSame($existingUserTable->id, $result->id);
        $this->assertSame(['job_number', 'status', 'engineer'], $result->columns);
    }

    public function testCreateUserTableWithEmptyColumns(): void
    {
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        $userTableDTO = UserTableDTO::fromArray([
            'user_id' => $user->id,
            'table_name' => 'jobs',
            'columns' => [],
        ]);

        $action = app(CreateOrUpdateUserTableAction::class);
        $result = $action->run($userTableDTO);

        $this->assertInstanceOf(UserTable::class, $result);
        $this->assertSame([], $result->columns);
    }

    public function testCreateUserTableAuthorizationFailure(): void
    {
        $user = User::factory()->user()->createOne();
        // Don't set actingAs to test authorization

        $userTableDTO = UserTableDTO::fromArray([
            'user_id' => $user->id,
            'table_name' => 'jobs',
            'columns' => ['job_number'],
        ]);

        $action = app(CreateOrUpdateUserTableAction::class);

        $this->expectException(AuthorizationException::class);

        $action->run($userTableDTO);
    }

    public function testCanCreateUserTableWithSuperAdminRole(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $userTableDTO = UserTableDTO::fromArray([
            'user_id' => $user->id,
            'table_name' => 'jobs',
            'columns' => ['job_number', 'customer'],
        ]);

        $action = app(CreateOrUpdateUserTableAction::class);
        $result = $action->run($userTableDTO);

        $this->assertInstanceOf(UserTable::class, $result);
        $this->assertSame(['job_number', 'customer'], $result->columns);
    }

    public function testCanCreateUserTableWithAdminRole(): void
    {
        $user = User::factory()->admin()->createOne();
        $this->actingAs($user);

        $userTableDTO = UserTableDTO::fromArray([
            'user_id' => $user->id,
            'table_name' => 'jobs',
            'columns' => ['job_number'],
        ]);

        $action = app(CreateOrUpdateUserTableAction::class);
        $result = $action->run($userTableDTO);

        $this->assertInstanceOf(UserTable::class, $result);
    }

    public function testUpdateUserTableWithDifferentColumns(): void
    {
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        $existingUserTable = UserTable::factory()
            ->forUser($user)
            ->withTableName('jobs')
            ->withColumns(['column1', 'column2'])
            ->createOne();

        $userTableDTO = UserTableDTO::fromArray([
            'user_id' => $user->id,
            'table_name' => 'jobs',
            'columns' => ['column3', 'column4', 'column5'],
        ]);

        $action = app(CreateOrUpdateUserTableAction::class);
        $result = $action->run($userTableDTO);

        $this->assertSame($existingUserTable->id, $result->id);
        $this->assertSame(['column3', 'column4', 'column5'], $result->columns);
    }
}
