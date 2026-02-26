<?php

namespace App\Containers\AppSection\User\Tests\Unit\Actions;

use App\Containers\AppSection\User\Actions\UpdatePasswordAction;
use App\Containers\AppSection\User\Data\DTOs\UserDTO;
use App\Containers\AppSection\User\Models\User;
use App\Containers\AppSection\User\Tests\UnitTestCase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UpdatePasswordAction::class)]
final class UpdatePasswordActionTest extends UnitTestCase
{
    public function testCanUpdateCurrentPassword(): void
    {
        $this->actingAs(User::factory()->superAdmin()->createOne([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'superadmin@the.grey',
            'password' => 'youShallNotPass',
        ]));

        $user = User::factory()->createOne(['password' => 'youShallNotPass']);
        $action = app(UpdatePasswordAction::class);

        $userDTO = new UserDTO(
            userId: $user->id,
            password: 'test',
        );

        $result = $action->run($userDTO);

        $this->assertTrue(Hash::check('test', $result->password));
    }
}
