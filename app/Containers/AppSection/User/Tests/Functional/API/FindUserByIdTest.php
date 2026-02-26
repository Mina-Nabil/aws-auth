<?php

namespace App\Containers\AppSection\User\Tests\Functional\API;

use App\Containers\AppSection\User\Models\User;
use App\Containers\AppSection\User\Tests\Functional\ApiTestCase;
use App\Containers\AppSection\User\API\Controllers\UserCRUDController;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UserCRUDController::class)]
final class FindUserByIdTest extends ApiTestCase
{
    public function testCanFindSelf(): void
    {
        /** @var User $user */
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        $response = $this->getJson(action([UserCRUDController::class, 'findUserById'], ['user_id' => $user->id]));

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json): AssertableJson => $json->has('data')
                ->where('data.id', $user->id)
                ->etc(),
        );
    }
}
