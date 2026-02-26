<?php

namespace App\Containers\AppSection\User\Tests\Functional\API;

use App\Containers\AppSection\User\Models\User;
use App\Containers\AppSection\User\Tests\Functional\ApiTestCase;
use App\Containers\AppSection\User\API\Controllers\UserCRUDController;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UserCRUDController::class)]
final class GetUserProfileTest extends ApiTestCase
{
    public function testCanGetOwnProfile(): void
    {
        $user = User::factory()->createOne();
        $this->actingAs($user);

        $response = $this->getJson(action([UserCRUDController::class, 'getLoggedInUserProfile']));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json): AssertableJson => $json->has(
                'data',
                static fn (AssertableJson $json): AssertableJson => $json
                    ->where('type', 'User')
                    ->where('id', $user->id)
                    ->where('email', $user->email)
                    ->whereType('email_verified_at', 'string')
                    ->where('first_name', $user->first_name)
                    ->where('last_name', $user->last_name)
                    ->whereType('is_disabled', 'boolean')
                    ->etc(),
            )->etc(),
        );
    }

    public function testPreventAccessByUnauthenticatedUser(): void
    {
        $response = $this->getJson(action([UserCRUDController::class, 'getLoggedInUserProfile']));

        $response->assertUnauthorized();
    }
}
