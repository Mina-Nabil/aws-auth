<?php

namespace App\Containers\AppSection\UserTable\Tests\Functional\API;

use App\Containers\AppSection\UserTable\API\Controllers\UserTableController;
use App\Containers\AppSection\UserTable\Models\UserTable;
use App\Containers\AppSection\UserTable\Tests\Functional\ApiTestCase;
use App\Containers\AppSection\User\Models\User;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UserTableController::class)]
final class DeleteUserTableTest extends ApiTestCase
{
    public function testCanDeleteUserTablePreferences(): void
    {
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        $userTable = UserTable::factory()
            ->forUser($user)
            ->withTableName('jobs')
            ->createOne();

        $response = $this->deleteJson(
            action([UserTableController::class, 'destroy'], ['table_name' => 'jobs'])
        );

        $response->assertNoContent();
        $this->assertDatabaseMissing('user_tables', ['id' => $userTable->id]);
    }

    public function testDeleteUserTablePreferencesReturnsNotFoundWhenNotExists(): void
    {
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        $response = $this->deleteJson(
            action([UserTableController::class, 'destroy'], ['table_name' => 'non-existent-table'])
        );

        $response->assertNotFound();
        $response->assertJson([
            'message' => 'Table preferences not found for table: non-existent-table',
        ]);
    }

    public function testDeleteUserTablePreferencesOnlyDeletesCurrentUserPreferences(): void
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

        $response = $this->deleteJson(
            action([UserTableController::class, 'destroy'], ['table_name' => 'jobs'])
        );

        $response->assertNoContent();
        $this->assertDatabaseMissing('user_tables', ['id' => $userTable1->id]);
        $this->assertDatabaseHas('user_tables', ['id' => $userTable2->id]);
    }

    public function testDeleteUserTablePreferencesWithDifferentTableNames(): void
    {
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        $userTable1 = UserTable::factory()
            ->forUser($user)
            ->withTableName('jobs')
            ->createOne();

        $userTable2 = UserTable::factory()
            ->forUser($user)
            ->withTableName('products')
            ->createOne();

        $response = $this->deleteJson(
            action([UserTableController::class, 'destroy'], ['table_name' => 'jobs'])
        );

        $response->assertNoContent();
        $this->assertDatabaseMissing('user_tables', ['id' => $userTable1->id]);
        $this->assertDatabaseHas('user_tables', ['id' => $userTable2->id]);
    }

    public function testGivenUserHasNoAccessPreventsOperation(): void
    {
        $user = User::factory()->user()->createOne();
        // Don't set actingAs to test unauthorized access

        UserTable::factory()
            ->forUser($user)
            ->withTableName('jobs')
            ->createOne();

        $response = $this->deleteJson(
            action([UserTableController::class, 'destroy'], ['table_name' => 'jobs'])
        );

        $response->assertUnauthorized();
    }

    public function testCanDeleteUserTablePreferencesWithSuperAdminRole(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $userTable = UserTable::factory()
            ->forUser($user)
            ->withTableName('jobs')
            ->createOne();

        $response = $this->deleteJson(
            action([UserTableController::class, 'destroy'], ['table_name' => 'jobs'])
        );

        $response->assertNoContent();
        $this->assertDatabaseMissing('user_tables', ['id' => $userTable->id]);
    }

    public function testCanDeleteUserTablePreferencesWithAdminRole(): void
    {
        $user = User::factory()->admin()->createOne();
        $this->actingAs($user);

        $userTable = UserTable::factory()
            ->forUser($user)
            ->withTableName('jobs')
            ->createOne();

        $response = $this->deleteJson(
            action([UserTableController::class, 'destroy'], ['table_name' => 'jobs'])
        );

        $response->assertNoContent();
        $this->assertDatabaseMissing('user_tables', ['id' => $userTable->id]);
    }

}
