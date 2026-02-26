<?php

namespace App\Containers\AppSection\User\Tests\Functional\API;

use App\Containers\AppSection\User\Models\User;
use App\Containers\AppSection\User\Tests\Functional\ApiTestCase;
use App\Containers\AppSection\User\API\Controllers\UserCRUDController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UserCRUDController::class)]
final class UpdatePasswordTest extends ApiTestCase
{
    public function testCanUpdatePasswordAsOwner(): void
    {
        /** @var User $user */
        $user = User::factory()->user()->createOne([
            'password' => 'Av@dakedavra!',
        ]);
        $this->actingAs($user);
        $data = [
            'current_password' => 'Av@dakedavra!',
            'new_password' => 'updated#Password111',
            'new_password_confirmation' => 'updated#Password111',
        ];

        $response = $this->patch(action(
            [UserCRUDController::class, 'updateUserPassword'],
            ['user_id' => $user->id],
        ), $data);

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json): AssertableJson => $json->has(
                'data',
                fn (AssertableJson $json): AssertableJson => $json
                    ->where('type', 'User')
                    ->where('email', $user->email)
                    ->missing('password')
                    ->etc(),
            )->etc(),
        );
        $this->assertTrue(Hash::check($data['new_password'], $user->refresh()->password));
    }

    // TODO: move to request test
    public function testGivenUserHasNoAccessPreventsOperation(): void
    {
        $this->actingAs(User::factory()->user()->createOne([
            'password' => Hash::make('Av@dakedavra!'),
        ]));

        $response = $this->patch(action(
            [UserCRUDController::class, 'updateUserPassword'],
            [
                'user_id' => User::factory()->createOne()->id,
                'current_password' => 'Av@dakedavra!',
                'new_password' => 'updated#Password111',
                'new_password_confirmation' => 'updated#Password111',
            ],
        ));

        $response->assertForbidden();
    }
}
