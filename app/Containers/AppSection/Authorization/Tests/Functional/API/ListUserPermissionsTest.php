<?php

namespace App\Containers\AppSection\Authorization\Tests\Functional\API;

use App\Containers\AppSection\Authorization\Models\Permission;
use App\Containers\AppSection\Authorization\Tests\Functional\ApiTestCase;
use App\Containers\AppSection\Authorization\API\Controllers\UserPermissionsCRUDController;
use App\Containers\AppSection\User\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UserPermissionsCRUDController::class)]
final class ListUserPermissionsTest extends ApiTestCase
{
    public function testGetUserPermissions(): void
    {
        $this->actingAs(User::factory()->superAdmin()->createOne());
        $user = User::factory()->createOne();
        $permission = Permission::factory()->createOne();
        $user->givePermissionTo([$permission]);

        $response = $this->getJson(action(
            [UserPermissionsCRUDController::class, 'index'],
            ['user_id' => $user->id],
        ));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has(
                'data',
                static fn (AssertableJson $json) => $json->where('0.name', $permission->name)
                ->etc(),
            )->etc(),
        );
    }

    // TODO: move to request test
    public function testGivenUserHasNoAccessPreventsOperation(): void
    {
        $this->actingAs(User::factory()->user()->createOne());

        $response = $this->getJson(action(
            [UserPermissionsCRUDController::class, 'index'],
            ['user_id' => User::factory()->createOne()->id],
        ));

        $response->assertForbidden();
    }
}
