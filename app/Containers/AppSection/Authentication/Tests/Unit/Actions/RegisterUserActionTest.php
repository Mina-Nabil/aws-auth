<?php

namespace App\Containers\AppSection\Authentication\Tests\Unit\Actions;

use App\Containers\AppSection\Authentication\Actions\RegisterUserAction;
use App\Containers\AppSection\Authentication\Tests\UnitTestCase;
use App\Containers\AppSection\Authorization\Enums\Role;
use App\Containers\AppSection\User\Data\DTOs\UserDTO;
use App\Containers\AppSection\User\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(RegisterUserAction::class)]
final class RegisterUserActionTest extends UnitTestCase
{
    public function testRegisterUser(): void
    {
        Notification::fake();
        Event::fake();
        $userDTO = new UserDTO(
            firstName: 'Gandalf',
            lastName: 'The Grey',
            email: 'gandalf@the.grey',
            password: 'youShallNotPass',
        );
        $action = app(RegisterUserAction::class);

        $user = $action->run($userDTO);

        $this->assertModelExists($user);
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame($userDTO->firstName, $user->first_name);
        $this->assertSame($userDTO->lastName, $user->last_name);
        $this->assertSame(strtolower($userDTO->email), $user->email);
        $this->assertTrue(Hash::check($userDTO->password, $user->password));
        $this->assertNull($user->email_verified_at);
        $this->assertEquals(Role::USER->value, $user->roles->first()->name);
        Event::assertDispatched(Registered::class, static fn (Registered $event) => $event->user->is($user));
    }
}
