<?php

namespace App\Containers\AppSection\User\Tests\Functional\API;

use App\Containers\AppSection\Authorization\Enums\AuthorizationGate;
use App\Containers\AppSection\Authorization\Enums\Role as RoleEnum;
use App\Containers\AppSection\Authorization\Models\Permission;
use App\Containers\AppSection\Authorization\Models\Role;
use App\Containers\AppSection\User\Models\User;
use App\Containers\AppSection\User\Tests\Functional\ApiTestCase;
use App\Containers\AppSection\User\API\Controllers\UserCRUDController;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UserCRUDController::class)]
final class UpdateUserTest extends ApiTestCase
{
    public function testCanUpdateAsOwner(): void
    {
        /** @var User $user */
        $user = User::factory()->user()->createOne([
            'first_name' => 'He who must not be named',
            'last_name' => 'Updated Name',
            'password' => 'Av@dakedavra!',
        ]);
        $this->actingAs($user);
        $data = [
            'first_name' => 'Updated Name',
            'last_name' => 'Updated Last Name',
        ];

        $response = $this->patchJson(URL::action([UserCRUDController::class, 'updateUserInfo'], $user->id), $data);

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json): AssertableJson => $json->has(
                'data',
                fn (AssertableJson $json): AssertableJson => $json
                    ->where('type', 'User')
                    ->where('email', $user->email)
                    ->where('first_name', $data['first_name'])
                    ->where('last_name', $data['last_name'])
                    ->missing('password')
                    ->etc(),
            )->etc(),
        );
    }

    public function testCanUpdateEmailAsOwner(): void
    {
        /** @var User $user */
        $user = User::factory()->user()->createOne([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'oldemail@example.com',
            'password' => 'Av@dakedavra!',
        ]);
        $this->actingAs($user);
        $data = [
            'email' => 'newemail@example.com',
        ];

        $response = $this->patchJson(URL::action([UserCRUDController::class, 'updateUserInfo'], $user->id), $data);

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json): AssertableJson => $json->has(
                'data',
                fn (AssertableJson $json): AssertableJson => $json
                    ->where('type', 'User')
                    ->where('email', $data['email'])
                    ->where('first_name', $user->first_name)
                    ->where('last_name', $user->last_name)
                    ->missing('password')
                    ->etc(),
            )->etc(),
        );
    }

    public function testCanUpdateRoleAsSuperAdmin(): void
    {
        $this->actingAs(User::factory()->superAdmin()->createOne());

        /** @var User $user */
        $user = User::factory()->user()->createOne([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'Av@dakedavra!',
        ]);

        // Create admin role if it doesn't exist
        Role::firstOrCreate(
            ['name' => RoleEnum::ADMIN->value, 'guard_name' => 'api']
        );

        $data = [
            'role' => RoleEnum::ADMIN->value,
        ];

        $response = $this->patchJson(URL::action([UserCRUDController::class, 'updateUserInfo'], $user->id), $data);

        $response->assertOk();
        $user->refresh();
        $this->assertTrue($user->hasRole(RoleEnum::ADMIN->value));
        $this->assertFalse($user->hasRole(RoleEnum::USER->value));
    }

    public function testCanUpdateEmailAndRoleAsSuperAdmin(): void
    {
        $this->actingAs(User::factory()->superAdmin()->createOne());

        /** @var User $user */
        $user = User::factory()->user()->createOne([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'oldemail@example.com',
            'password' => 'Av@dakedavra!',
        ]);

        // Create admin role if it doesn't exist
        Role::firstOrCreate(
            ['name' => RoleEnum::ADMIN->value, 'guard_name' => 'api']
        );

        $data = [
            'email' => 'newemail@example.com',
            'role' => RoleEnum::ADMIN->value,
        ];

        $response = $this->patchJson(URL::action([UserCRUDController::class, 'updateUserInfo'], $user->id), $data);

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json): AssertableJson => $json->has(
                'data',
                fn (AssertableJson $json): AssertableJson => $json
                    ->where('type', 'User')
                    ->where('email', $data['email'])
                    ->where('first_name', $user->first_name)
                    ->where('last_name', $user->last_name)
                    ->missing('password')
                    ->etc(),
            )->etc(),
        );
        $user->refresh();
        $this->assertTrue($user->hasRole(RoleEnum::ADMIN->value));
        $this->assertFalse($user->hasRole(RoleEnum::USER->value));
    }

    public function testCannotUpdateRoleWithoutPermission(): void
    {
        // Create a user with update-my-user permission but not update-any-user
        // This user can update themselves but cannot update roles
        $actingUser = User::factory()->user()->createOne([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'Av@dakedavra!',
        ]);
        $updateMyUserPermission = Permission::firstOrCreate(
            ['name' => 'update-my-user', 'guard_name' => 'api']
        );
        $actingUser->givePermissionTo($updateMyUserPermission);
        $this->actingAs($actingUser);

        // Create admin role if it doesn't exist
        Role::firstOrCreate(
            ['name' => RoleEnum::ADMIN->value, 'guard_name' => 'api']
        );

        // Try to update own role - should fail because update-any-user is required for role updates
        $data = [
            'role' => RoleEnum::ADMIN->value,
        ];

        $response = $this->patchJson(URL::action([UserCRUDController::class, 'updateUserInfo'], $actingUser->id), $data);

        $response->assertForbidden();
    }

    public function testSuperAdminCanUpdateUserRole(): void
    {
        $this->actingAs(User::factory()->superAdmin()->createOne());

        /** @var User $user */
        $user = User::factory()->user()->createOne([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'Av@dakedavra!',
        ]);

        // Create admin role if it doesn't exist
        Role::firstOrCreate(
            ['name' => RoleEnum::ADMIN->value, 'guard_name' => 'api']
        );

        $data = [
            'role' => RoleEnum::ADMIN->value,
        ];

        $response = $this->patchJson(URL::action([UserCRUDController::class, 'updateUserInfo'], $user->id), $data);

        $response->assertOk();
        $user->refresh();
        $this->assertTrue($user->hasRole(RoleEnum::ADMIN->value));
        $this->assertFalse($user->hasRole(RoleEnum::USER->value));
    }

    public function testCanUpdateEmailWithoutRolePermissionButCannotUpdateRole(): void
    {
        // Create a user with update-my-user permission but not update-any-user
        $actingUser = User::factory()->user()->createOne([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'oldemail@example.com',
            'password' => 'Av@dakedavra!',
        ]);
        $updateMyUserPermission = Permission::firstOrCreate(
            ['name' => 'update-my-user', 'guard_name' => 'api']
        );
        $actingUser->givePermissionTo($updateMyUserPermission);
        $this->actingAs($actingUser);

        // Create admin role if it doesn't exist
        Role::firstOrCreate(
            ['name' => RoleEnum::ADMIN->value, 'guard_name' => 'api']
        );

        // Should be able to update own email
        $data = [
            'email' => 'newemail@example.com',
        ];

        $response = $this->patchJson(URL::action([UserCRUDController::class, 'updateUserInfo'], $actingUser->id), $data);
        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json): AssertableJson => $json->has(
                'data',
                fn (AssertableJson $json): AssertableJson => $json
                    ->where('type', 'User')
                    ->where('email', $data['email'])
                    ->etc(),
            )->etc(),
        );

        // But cannot update own role (requires update-any-user permission)
        $dataWithRole = [
            'email' => 'newemail@example.com',
            'role' => RoleEnum::ADMIN->value,
        ];

        $response = $this->patchJson(URL::action([UserCRUDController::class, 'updateUserInfo'], $actingUser->id), $dataWithRole);
        $response->assertForbidden();
    }

    // TODO: move to request test
    public function testGivenUserHasNoAccessPreventsOperation(): void
    {
        $this->actingAs(User::factory()->user()->createOne());

        $response = $this->patchJson(URL::action([UserCRUDController::class, 'updateUserInfo'], User::factory()->createOne()->id));

        $response->assertForbidden();
    }
}
