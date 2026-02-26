<?php

namespace App\Containers\AppSection\User\Tests\Unit\Tasks;

use App\Containers\AppSection\User\Data\DTOs\UserDTO;
use App\Containers\AppSection\User\Models\User;
use App\Containers\AppSection\User\Tasks\UpdateUserTask;
use App\Containers\AppSection\User\Tests\UnitTestCase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UpdateUserTask::class)]
final class UpdateUserTaskTest extends UnitTestCase
{
    public function testUpdateUser(): void
    {
        $user = User::factory()->createOne();

        $userDTO = new UserDTO(
            userId: $user->id,
            firstName: 'new name',
            lastName: 'new last name',
        );

        $updatedUser = app(UpdateUserTask::class)->run($userDTO);

        $this->assertSame($user->id, $updatedUser->id);
        $this->assertSame($userDTO->firstName, $updatedUser->first_name);
        $this->assertSame($userDTO->lastName, $updatedUser->last_name);
    }

    public function testUpdatedPasswordShouldBeHashed(): void
    {
        $user = User::factory()->createOne();

        $userDTO = new UserDTO(userId: $user->id, password: 'secret');

        $result = app(UpdateUserTask::class)->run($userDTO);

        $this->assertTrue(Hash::check('secret', $result->password));
    }
}
