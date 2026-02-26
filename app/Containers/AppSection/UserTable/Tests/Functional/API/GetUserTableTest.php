<?php

namespace App\Containers\AppSection\UserTable\Tests\Functional\API;

use App\Containers\AppSection\UserTable\API\Controllers\UserTableController;
use App\Containers\AppSection\UserTable\Models\UserTable;
use App\Containers\AppSection\UserTable\Tests\Functional\ApiTestCase;
use App\Containers\AppSection\User\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UserTableController::class)]
final class GetUserTableTest extends ApiTestCase
{
    public function testCanGetUserTablePreferences(): void
    {
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        $userTable = UserTable::factory()
            ->forUser($user)
            ->withTableName('jobs')
            ->withColumns(['job_number', 'customer', 'status'])
            ->createOne();

        $response = $this->getJson(
            action([UserTableController::class, 'show'], ['table_name' => 'jobs'])
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
    }

    public function testGetUserTablePreferencesReturnsEmptyColumns(): void
    {
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        UserTable::factory()
            ->forUser($user)
            ->withTableName('jobs')
            ->withColumns([])
            ->createOne();

        $response = $this->getJson(
            action([UserTableController::class, 'show'], ['table_name' => 'jobs'])
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

    public function testGetUserTablePreferencesReturnsNotFoundWhenNotExists(): void
    {
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        $response = $this->getJson(
            action([UserTableController::class, 'show'], ['table_name' => 'non-existent-table'])
        );

        $response->assertNotFound();
        $response->assertJson([
            'message' => 'Table preferences not found for table: non-existent-table',
        ]);
    }

    public function testGetUserTablePreferencesOnlyReturnsCurrentUserPreferences(): void
    {
        $user1 = User::factory()->user()->createOne();
        $user2 = User::factory()->user()->createOne();
        $this->actingAs($user1);

        UserTable::factory()
            ->forUser($user1)
            ->withTableName('jobs')
            ->withColumns(['col1'])
            ->createOne();

        UserTable::factory()
            ->forUser($user2)
            ->withTableName('jobs')
            ->withColumns(['col2'])
            ->createOne();

        $response = $this->getJson(
            action([UserTableController::class, 'show'], ['table_name' => 'jobs'])
        );

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has(
                'data',
                static fn (AssertableJson $json) => $json->where('visible_columns.0', 'col1')
                    ->etc(),
            )->etc(),
        );
    }

    public function testGivenUserHasNoAccessPreventsOperation(): void
    {
        $response = $this->getJson(
            action([UserTableController::class, 'show'], ['table_name' => 'jobs'])
        );

        $response->assertUnauthorized();
    }

    public function testCanGetUserTablePreferencesWithSuperAdminRole(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        UserTable::factory()
            ->forUser($user)
            ->withTableName('jobs')
            ->withColumns(['job_number', 'status'])
            ->createOne();

        $response = $this->getJson(
            action([UserTableController::class, 'show'], ['table_name' => 'jobs'])
        );

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has(
                'data',
                static fn (AssertableJson $json) => $json->has('visible_columns', 2)
                    ->etc(),
            )->etc(),
        );
    }

    public function testCanGetUserTablePreferencesWithAdminRole(): void
    {
        $user = User::factory()->admin()->createOne();
        $this->actingAs($user);

        UserTable::factory()
            ->forUser($user)
            ->withTableName('jobs')
            ->withColumns(['job_number'])
            ->createOne();

        $response = $this->getJson(
            action([UserTableController::class, 'show'], ['table_name' => 'jobs'])
        );

        $response->assertOk();
    }

    public function testGetUserTablePreferencesWithDifferentTableNames(): void
    {
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        UserTable::factory()
            ->forUser($user)
            ->withTableName('jobs')
            ->withColumns(['job_number'])
            ->createOne();

        UserTable::factory()
            ->forUser($user)
            ->withTableName('products')
            ->withColumns(['product_name'])
            ->createOne();

        $response1 = $this->getJson(
            action([UserTableController::class, 'show'], ['table_name' => 'jobs'])
        );

        $response2 = $this->getJson(
            action([UserTableController::class, 'show'], ['table_name' => 'products'])
        );

        $response1->assertOk();
        $response1->assertJson(
            static fn (AssertableJson $json) => $json->has(
                'data',
                static fn (AssertableJson $json) => $json->where('visible_columns.0', 'job_number')
                    ->etc(),
            )->etc(),
        );

        $response2->assertOk();
        $response2->assertJson(
            static fn (AssertableJson $json) => $json->has(
                'data',
                static fn (AssertableJson $json) => $json->where('visible_columns.0', 'product_name')
                    ->etc(),
            )->etc(),
        );
    }
}
