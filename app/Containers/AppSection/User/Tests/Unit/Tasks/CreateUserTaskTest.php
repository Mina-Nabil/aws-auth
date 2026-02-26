<?php

namespace App\Containers\AppSection\User\Tests\Unit\Tasks;

use App\Containers\AppSection\User\Data\DTOs\UserDTO;
use App\Containers\AppSection\User\Models\User;
use App\Containers\AppSection\User\Tasks\CreateUserTask;
use App\Containers\AppSection\User\Tests\UnitTestCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CreateUserTask::class)]
final class CreateUserTaskTest extends UnitTestCase
{
    public function testCreateUser(): void
    {
        $newUserDTO = new UserDTO(
            firstName: 'Gandalf',
            lastName: 'The Grey',
            email: 'gandalf@the.grey',
            password: 'youShallNotPass',
            role: 'super-admin',
        );

        $user = app(CreateUserTask::class)->run($newUserDTO);

        $this->assertModelExists($user);
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame(Str::lower($newUserDTO->email), $user->email);
        $this->assertTrue(Hash::check($newUserDTO->password, $user->password));
    }
}
