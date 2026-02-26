<?php

namespace App\Containers\AppSection\Authorization\Tests\Functional\API;

use App\Containers\AppSection\Authorization\Models\Role;
use App\Containers\AppSection\Authorization\Tests\Functional\ApiTestCase;
use App\Containers\AppSection\Authorization\API\Controllers\UserRolesCRUDController;
use App\Containers\AppSection\User\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UserRolesCRUDController::class)]
final class AssignRolesToUserTest extends ApiTestCase
{
    public function testAssignRoleToUser(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);
        $role = Role::factory()->createOne();
        $data = [
            'role_ids' => [$role->id],
        ];

        $response = $this->patchJson(action(
            [UserRolesCRUDController::class, 'assign'],
            ['user_id' => $user->id],
        ), $data);

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json): AssertableJson => $json->has('data')
                ->has('data.roles.data', 2)
                ->etc(),
        );
    }

    public function testAssignManyRolesToUser(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);
        $role1 = Role::factory()->createOne();
        $role2 = Role::factory()->createOne();
        $data = [
            'role_ids' => [
                $role1->id,
                $role2->id,
            ],
        ];

        $response = $this->patchJson(action(
            [UserRolesCRUDController::class, 'assign'],
            ['user_id' => $user->id],
        ), $data);

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json): AssertableJson => $json->has('data')
                ->has('data.roles.data', 3)
                ->etc(),
        );
    }

    // TODO: move to request test
    public function testGivenUserHasNoAccessPreventsOperation(): void
    {
        $this->actingAs(User::factory()->user()->createOne());

        $response = $this->patchJson(action(
            [UserRolesCRUDController::class, 'assign'],
            ['user_id' => User::factory()->createOne()->id],
        ));

        $response->assertForbidden();
    }
}
