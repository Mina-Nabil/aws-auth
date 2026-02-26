<?php

namespace App\Containers\AppSection\UserTable\Tests\Functional\API;

use App\Containers\AppSection\UserTable\API\Controllers\UserTableController;
use App\Containers\AppSection\UserTable\Models\UserTable;
use App\Containers\AppSection\UserTable\Tests\Functional\ApiTestCase;
use App\Containers\AppSection\User\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UserTableController::class)]
final class CreateOrUpdateUserTableTest extends ApiTestCase
{
    public function testCanCreateUserTablePreferences(): void
    {
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        $data = [
            'visible_columns' => ['job_number', 'customer', 'status'],
        ];

        $response = $this->putJson(
            action([UserTableController::class, 'store'], ['table_name' => 'jobs']),
            $data
        );

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has(
                'data',
                static fn (AssertableJson $json) => $json->has('visible_columns', 3)
                    ->where('visible_columns.0', 'job_number')
                    ->where('visible_columns.1', 'customer')
                    ->where('visible_columns.2', 'status')
                    ->etc(),
            )->etc(),
        );

        $this->assertDatabaseHas('user_tables', [
            'user_id' => $user->id,
            'table_name' => 'jobs',
        ]);

        $userTable = UserTable::where('user_id', $user->id)
            ->where('table_name', 'jobs')
            ->first();
        $this->assertSame(['job_number', 'customer', 'status'], $userTable->columns);
    }

    public function testCanUpdateExistingUserTablePreferences(): void
    {
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        $existingUserTable = UserTable::factory()
            ->forUser($user)
            ->withTableName('jobs')
            ->withColumns(['old_column'])
            ->createOne();

        $data = [
            'visible_columns' => ['job_number', 'status', 'engineer'],
        ];

        $response = $this->putJson(
            action([UserTableController::class, 'store'], ['table_name' => 'jobs']),
            $data
        );

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has(
                'data',
                static fn (AssertableJson $json) => $json->has('visible_columns', 3)
                    ->where('visible_columns.0', 'job_number')
                    ->where('visible_columns.1', 'status')
                    ->where('visible_columns.2', 'engineer')
                    ->etc(),
            )->etc(),
        );

        $existingUserTable->refresh();
        $this->assertSame(['job_number', 'status', 'engineer'], $existingUserTable->columns);
        $this->assertDatabaseCount('user_tables', 1);
    }

    public function testCreateUserTablePreferencesWithEmptyColumns(): void
    {
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        $data = [
            'visible_columns' => [],
        ];

        $response = $this->putJson(
            action([UserTableController::class, 'store'], ['table_name' => 'jobs']),
            $data
        );

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has(
                'data',
                static fn (AssertableJson $json) => $json->has('visible_columns', 0)
                    ->etc(),
            )->etc(),
        );
    }

    public function testCreateUserTablePreferencesWithSingleColumn(): void
    {
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        $data = [
            'visible_columns' => ['job_number'],
        ];

        $response = $this->putJson(
            action([UserTableController::class, 'store'], ['table_name' => 'jobs']),
            $data
        );

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has(
                'data',
                static fn (AssertableJson $json) => $json->has('visible_columns', 1)
                    ->where('visible_columns.0', 'job_number')
                    ->etc(),
            )->etc(),
        );
    }

    public function testCreateUserTablePreferencesWithManyColumns(): void
    {
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        $columns = ['col1', 'col2', 'col3', 'col4', 'col5', 'col6', 'col7', 'col8', 'col9', 'col10'];
        $data = [
            'visible_columns' => $columns,
        ];

        $response = $this->putJson(
            action([UserTableController::class, 'store'], ['table_name' => 'jobs']),
            $data
        );

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has(
                'data',
                static fn (AssertableJson $json) => $json->has('visible_columns', 10)
                    ->etc(),
            )->etc(),
        );
    }

    public function testGivenUserHasNoAccessPreventsOperation(): void
    {
        $data = [
            'visible_columns' => ['job_number'],
        ];

        $response = $this->putJson(
            action([UserTableController::class, 'store'], ['table_name' => 'jobs']),
            $data
        );

        $response->assertUnauthorized();
    }

    public function testCreateUserTablePreferencesWithInvalidDataFails(): void
    {
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        $data = [
            // Missing visible_columns
        ];

        $response = $this->putJson(
            action([UserTableController::class, 'store'], ['table_name' => 'jobs']),
            $data
        );

        $response->assertUnprocessable();
    }

    public function testCreateUserTablePreferencesWithNonArrayColumnsFails(): void
    {
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        $data = [
            'visible_columns' => 'not-an-array',
        ];

        $response = $this->putJson(
            action([UserTableController::class, 'store'], ['table_name' => 'jobs']),
            $data
        );

        $response->assertUnprocessable();
    }

    public function testCreateUserTablePreferencesWithNonStringColumnValuesFails(): void
    {
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        $data = [
            'visible_columns' => [123, 456], // Non-string values
        ];

        $response = $this->putJson(
            action([UserTableController::class, 'store'], ['table_name' => 'jobs']),
            $data
        );

        $response->assertUnprocessable();
    }

    public function testCanCreateUserTablePreferencesWithSuperAdminRole(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $data = [
            'visible_columns' => ['job_number', 'status'],
        ];

        $response = $this->putJson(
            action([UserTableController::class, 'store'], ['table_name' => 'jobs']),
            $data
        );

        $response->assertOk();
        $this->assertDatabaseHas('user_tables', [
            'user_id' => $user->id,
            'table_name' => 'jobs',
        ]);
    }

    public function testCanCreateUserTablePreferencesWithAdminRole(): void
    {
        $user = User::factory()->admin()->createOne();
        $this->actingAs($user);

        $data = [
            'visible_columns' => ['job_number'],
        ];

        $response = $this->putJson(
            action([UserTableController::class, 'store'], ['table_name' => 'jobs']),
            $data
        );

        $response->assertOk();
    }

    public function testCreateUserTablePreferencesOnlyCreatesForCurrentUser(): void
    {
        $user1 = User::factory()->user()->createOne();
        $user2 = User::factory()->user()->createOne();
        $this->actingAs($user1);

        $data = [
            'visible_columns' => ['job_number'],
        ];

        $response = $this->putJson(
            action([UserTableController::class, 'store'], ['table_name' => 'jobs']),
            $data
        );

        $response->assertOk();

        $this->assertDatabaseHas('user_tables', [
            'user_id' => $user1->id,
            'table_name' => 'jobs',
        ]);

        $this->assertDatabaseMissing('user_tables', [
            'user_id' => $user2->id,
            'table_name' => 'jobs',
        ]);
    }

    public function testCreateUserTablePreferencesWithLongTableName(): void
    {
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        $longTableName = str_repeat('a', 255);
        $data = [
            'visible_columns' => ['job_number'],
        ];

        $response = $this->putJson(
            action([UserTableController::class, 'store'], ['table_name' => $longTableName]),
            $data
        );

        $response->assertOk();
    }
}
