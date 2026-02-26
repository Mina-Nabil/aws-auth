<?php

namespace App\Containers\AppSection\User\Tests\Unit\Actions;

use App\Containers\AppSection\User\Actions\CreateUserAction;
use App\Containers\AppSection\User\Data\DTOs\UserDTO;
use App\Containers\AppSection\User\Models\User;
use App\Containers\AppSection\User\Notifications\WelcomeNotification;
use App\Containers\AppSection\User\Tests\UnitTestCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CreateUserAction::class)]
final class CreateUserActionTest extends UnitTestCase
{
    public function testCreateAdminInRequirePwResetMode(): void
    {
        config(['appSection-authorization.require_pw_reset' => true]);

        Notification::fake();

        $this->actingAs(User::factory()->superAdmin()->createOne([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'superadmin@the.grey',
        ]));
        $adminDTO = new UserDTO(
            firstName: 'Gandalf',
            lastName: 'The Grey',
            email: 'gandalf@the.grey',
            role: 'super-admin',
        );

        $admin = app(CreateUserAction::class)->run($adminDTO);

        $this->assertSame($adminDTO->email, $admin->email);
        $this->assertSame($adminDTO->firstName, $admin->first_name);
        $this->assertSame($adminDTO->lastName, $admin->last_name);
        $this->assertSame($adminDTO->role, $admin->roles->first()->name);
        $this->assertTrue($admin->isSuperAdmin());
        $this->assertNotNull($admin->email_verified_at);
        $this->assertTrue($admin->password_reset_required);
        $this->assertNotNull($admin->password);

        Notification::assertSentTo($admin, WelcomeNotification::class);

    }

    public function testCreateAdminInNotRequirePwResetMode(): void
    {
        config(['appSection-authorization.require_pw_reset' => false]);

        Notification::fake();

        $this->actingAs(User::factory()->superAdmin()->createOne([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'superadmin@the.grey',
        ]));
        $adminDTO = new UserDTO(
            firstName: 'Gandalf',
            lastName: 'The Grey',
            email: 'gandalf@the.grey',
            password: 'youShallNotPass',
            role: 'super-admin',
        );

        $admin = app(CreateUserAction::class)->run($adminDTO);

        $this->assertSame($adminDTO->email, $admin->email);
        $this->assertSame($adminDTO->firstName, $admin->first_name);
        $this->assertSame($adminDTO->lastName, $admin->last_name);
        $this->assertSame($adminDTO->role, $admin->roles->first()->name);
        $this->assertTrue($admin->isSuperAdmin());
        $this->assertNotNull($admin->email_verified_at);
        $this->assertNotTrue($admin->password_reset_required);
        $this->assertTrue(Hash::check($adminDTO->password, $admin->password));

        Notification::assertSentTo($admin, WelcomeNotification::class);

    }

    public function testGivenInvalidDataThrowExceptionAndRollbackDB(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $data = new UserDTO(
            firstName: 'Gandalf',
            lastName: 'The Grey',
            email: 'aaaaaa', //invalid email
            role: 'super_admin',
        );

        $admin = app(CreateUserAction::class)->run($data);

        $this->assertModelMissing($admin);
    }
}
