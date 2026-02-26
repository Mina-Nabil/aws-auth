<?php

namespace App\Containers\AppSection\User\Tests\Unit\Actions;

use App\Containers\AppSection\Authorization\Enums\AuthorizationGate;
use App\Containers\AppSection\Authorization\Enums\Role as RoleEnum;
use App\Containers\AppSection\Authorization\Models\Permission;
use App\Containers\AppSection\Authorization\Models\Role;
use App\Containers\AppSection\User\Actions\UpdateUserAction;
use App\Containers\AppSection\User\Data\DTOs\UserDTO;
use App\Containers\AppSection\User\Models\User;
use App\Containers\AppSection\User\Tests\UnitTestCase;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UpdateUserAction::class)]
final class UpdateUserActionTest extends UnitTestCase
{
    public function testCanUpdateUser(): void
    {
        $this->actingAs(User::factory()->superAdmin()->createOne([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'superadmin@the.grey',
            'password' => 'youShallNotPass',
        ]));

        $user = User::factory()
            ->createOne([
                'password' => 'youShallNotPass',
                'first_name' => 'frodo',
                'last_name' => 'baggins',
                'email' => 'lord@rings.com'
            ]);

        $action = app(UpdateUserAction::class);

        $userDTO = new UserDTO(
            userId: $user->id,
            firstName: 'sam',
            lastName: 'wise',
            password: 'test',
        );

        $result = $action->run($userDTO);

        $this->assertSame($userDTO->firstName, $result->first_name);
        $this->assertSame($userDTO->lastName, $result->last_name);
        $this->assertTrue(Hash::check($userDTO->password, $result->password));
    }

    public function testCanUpdateUserEmail(): void
    {
        $this->actingAs(User::factory()->superAdmin()->createOne([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'superadmin@the.grey',
            'password' => 'youShallNotPass',
        ]));

        $user = User::factory()
            ->createOne([
                'password' => 'youShallNotPass',
                'first_name' => 'frodo',
                'last_name' => 'baggins',
                'email' => 'lord@rings.com'
            ]);

        $action = app(UpdateUserAction::class);

        $newEmail = 'newemail@rings.com';
        $userDTO = new UserDTO(
            userId: $user->id,
            email: $newEmail,
        );

        $result = $action->run($userDTO);

        $this->assertSame($newEmail, $result->email);
    }

    public function testCanUpdateUserRole(): void
    {
        $this->actingAs(User::factory()->superAdmin()->createOne([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'superadmin@the.grey',
            'password' => 'youShallNotPass',
        ]));

        $user = User::factory()
            ->user()
            ->createOne([
                'password' => 'youShallNotPass',
                'first_name' => 'frodo',
                'last_name' => 'baggins',
                'email' => 'lord@rings.com'
            ]);

        // Create admin role if it doesn't exist
        Role::firstOrCreate(
            ['name' => RoleEnum::ADMIN->value, 'guard_name' => 'api']
        );

        $action = app(UpdateUserAction::class);

        $userDTO = new UserDTO(
            userId: $user->id,
            role: RoleEnum::ADMIN->value,
        );

        $result = $action->run($userDTO);

        $this->assertTrue($result->hasRole(RoleEnum::ADMIN->value));
        $this->assertFalse($result->hasRole(RoleEnum::USER->value));
    }

    public function testCanUpdateUserEmailAndRole(): void
    {
        $this->actingAs(User::factory()->superAdmin()->createOne([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'superadmin@the.grey',
            'password' => 'youShallNotPass',
        ]));

        $user = User::factory()
            ->user()
            ->createOne([
                'password' => 'youShallNotPass',
                'first_name' => 'frodo',
                'last_name' => 'baggins',
                'email' => 'lord@rings.com'
            ]);

        // Create admin role if it doesn't exist
        Role::firstOrCreate(
            ['name' => RoleEnum::ADMIN->value, 'guard_name' => 'api']
        );

        $action = app(UpdateUserAction::class);

        $newEmail = 'newemail@rings.com';
        $userDTO = new UserDTO(
            userId: $user->id,
            email: $newEmail,
            role: RoleEnum::ADMIN->value,
        );

        $result = $action->run($userDTO);

        $this->assertSame($newEmail, $result->email);
        $this->assertTrue($result->hasRole(RoleEnum::ADMIN->value));
        $this->assertFalse($result->hasRole(RoleEnum::USER->value));
    }

    public function testCannotUpdateUserRoleWithoutPermission(): void
    {
        // Create a user with update-my-user permission but not update-any-user
        // This user can update themselves but cannot update roles
        $actingUser = User::factory()->user()->createOne();
        $updateMyUserPermission = Permission::firstOrCreate(
            ['name' => 'update-my-user', 'guard_name' => 'api']
        );
        $actingUser->givePermissionTo($updateMyUserPermission);
        $this->actingAs($actingUser);

        // Create admin role if it doesn't exist
        Role::firstOrCreate(
            ['name' => RoleEnum::ADMIN->value, 'guard_name' => 'api']
        );

        $action = app(UpdateUserAction::class);

        // Try to update own role - should fail because update-any-user is required for role updates
        $userDTO = new UserDTO(
            userId: $actingUser->id,
            role: RoleEnum::ADMIN->value,
        );

        $this->expectException(AuthorizationException::class);
        $action->run($userDTO);
    }

    public function testSuperAdminCanUpdateUserRole(): void
    {
        $this->actingAs(User::factory()->superAdmin()->createOne());

        $user = User::factory()
            ->user()
            ->createOne([
                'password' => 'youShallNotPass',
                'first_name' => 'frodo',
                'last_name' => 'baggins',
                'email' => 'lord@rings.com'
            ]);

        // Create admin role if it doesn't exist
        Role::firstOrCreate(
            ['name' => RoleEnum::ADMIN->value, 'guard_name' => 'api']
        );

        $action = app(UpdateUserAction::class);

        $userDTO = new UserDTO(
            userId: $user->id,
            role: RoleEnum::ADMIN->value,
        );

        $result = $action->run($userDTO);

        $this->assertTrue($result->hasRole(RoleEnum::ADMIN->value));
        $this->assertFalse($result->hasRole(RoleEnum::USER->value));
    }

    public function testCanUpdateEmailWithoutRolePermissionButCannotUpdateRole(): void
    {
        // Create a user with update-my-user permission but not update-any-user
        $actingUser = User::factory()->user()->createOne();
        $updateMyUserPermission = Permission::firstOrCreate(
            ['name' => 'update-my-user', 'guard_name' => 'api']
        );
        $actingUser->givePermissionTo($updateMyUserPermission);
        $this->actingAs($actingUser);

        // Create admin role if it doesn't exist
        Role::firstOrCreate(
            ['name' => RoleEnum::ADMIN->value, 'guard_name' => 'api']
        );

        $action = app(UpdateUserAction::class);

        // Should be able to update own email
        $newEmail = 'newemail@rings.com';
        $userDTO = new UserDTO(
            userId: $actingUser->id,
            email: $newEmail,
        );

        $result = $action->run($userDTO);
        $this->assertSame($newEmail, $result->email);

        // But cannot update own role (requires update-any-user permission)
        $userDTOWithRole = new UserDTO(
            userId: $actingUser->id,
            email: $newEmail,
            role: RoleEnum::ADMIN->value,
        );

        $this->expectException(AuthorizationException::class);
        $action->run($userDTOWithRole);
    }
}
