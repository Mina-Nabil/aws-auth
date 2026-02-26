<?php

namespace App\Containers\AppSection\User\Tests\Functional\API;

use App\Containers\AppSection\User\Models\User;
use App\Containers\AppSection\User\Tests\Functional\ApiTestCase;
use App\Containers\AppSection\User\API\Controllers\UserCRUDController;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UserCRUDController::class)]
final class DeleteUserTest extends ApiTestCase
{
    public function testAdminCanDeleteAnotherUser(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $response = $this->deleteJson(action(
            [UserCRUDController::class, 'deleteUser'],
            ['user_id' => User::factory()->createOne()->id],
        ));

        $response->assertNoContent();
    }

    public function testCannotDeleteSelf(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $response = $this->deleteJson(action(
            [UserCRUDController::class, 'deleteUser'],
            ['user_id' => $user->id],
        ));

        $response->assertForbidden();
    }

    public function testGivenUserHasNoAccessPreventsOperation(): void
    {
        $this->actingAs(User::factory()->createOne());

        $response = $this->deleteJson(action(
            [UserCRUDController::class, 'deleteUser'],
            ['user_id' => User::factory()->createOne()->id],
        ));

        $response->assertForbidden();
    }
}
