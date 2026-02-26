<?php

namespace App\Containers\AppSection\Authorization\Tests\Functional\API;

use App\Containers\AppSection\Authorization\Models\Role;
use App\Containers\AppSection\Authorization\Tests\Functional\ApiTestCase;
use App\Containers\AppSection\Authorization\API\Controllers\UserRolesCRUDController;
use App\Containers\AppSection\User\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UserRolesCRUDController::class)]
final class RemoveUserRolesTest extends ApiTestCase
{
    public function testRevokeRolesFromUser(): void
    {
        $this->actingAs(User::factory()->superAdmin()->createOne());
        $roleA = Role::factory()->createOne();
        $roleB = Role::factory()->createOne();
        $user = User::factory()->createOne();
        $user->assignRole($roleA, $roleB);
        $data = [
            'role_ids' => [$roleA->id],
        ];

        $response = $this->deleteJson(action([UserRolesCRUDController::class, 'destroy'], ['user_id' => $user->id]), $data);

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json): AssertableJson => $json->has('data')
                ->where('data.type', 'User')
                ->where('data.id', $user->id)
                ->has('data.roles.data', 1)
                ->where('data.roles.data.0.id', $roleB->id)
                ->etc(),
        );
    }

    public function testRevokeManyRolesFromUser(): void
    {
        $this->actingAs(User::factory()->superAdmin()->createOne());
        $roleA = Role::factory()->createOne();
        $roleB = Role::factory()->createOne();
        $user = User::factory()->createOne();
        $user->assignRole($roleA);
        $user->assignRole($roleB);

        $data = [
            'role_ids' => [$roleA->id, $roleB->id],
        ];

        $response = $this->deleteJson(action([UserRolesCRUDController::class, 'destroy'], ['user_id' => $user->id]), $data);

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json): AssertableJson => $json->has('data')
                ->where('data.type', 'User')
                ->where('data.id', $user->id)
                ->has('data.roles.data', 0)
                ->etc(),
        );
    }

    // TODO: move to request test
    public function testGivenUserHasNoAccessPreventsOperation(): void
    {
        $this->actingAs(User::factory()->user()->createOne());

        $response = $this->deleteJson(action([UserRolesCRUDController::class, 'destroy'], ['user_id' => User::factory()->createOne()->id]));

        $response->assertForbidden();
    }
}
