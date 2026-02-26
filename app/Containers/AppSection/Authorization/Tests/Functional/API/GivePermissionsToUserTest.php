<?php

namespace App\Containers\AppSection\Authorization\Tests\Functional\API;

use App\Containers\AppSection\Authorization\Models\Permission;
use App\Containers\AppSection\Authorization\Tests\Functional\ApiTestCase;
use App\Containers\AppSection\Authorization\API\Controllers\UserPermissionsCRUDController;
use App\Containers\AppSection\User\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UserPermissionsCRUDController::class)]
final class GivePermissionsToUserTest extends ApiTestCase
{
    public function testGiveSinglePermission(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $currentPermissionsCount = $user->getAllPermissions()->count();

        $permission = Permission::factory()->createOne();
        $data = [
            'permission_ids' => [$permission->id],
        ];

        $response = $this->postJson(action([UserPermissionsCRUDController::class, 'assign'], ['user_id' => $user->id]), $data);

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json): AssertableJson => $json->has('data')
                ->where('data.type', 'User')
                ->where('data.id', $user->id)
                ->has('data.permissions.data', $currentPermissionsCount + 1)
                ->where('data.permissions.data.0.type', 'Permission')
                ->where('data.permissions.data.0.id', $permission->id)
                ->etc(),
        );
    }

    public function testGiveMultiplePermissions(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $currentPermissionsCount = $user->getAllPermissions()->count();

        $permissionA = Permission::factory()->createOne();
        sleep(1);
        $permissionB = Permission::factory()->createOne();
        sleep(1);
        $data = [
            'permission_ids' => [$permissionA->id, $permissionB->id],
        ];

        $response = $this->postJson(action([UserPermissionsCRUDController::class, 'assign'], ['user_id' => $user->id]), $data);

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json): AssertableJson => $json->has('data')
                ->where('data.type', 'User')
                ->where('data.id', $user->id)
                ->has('data.permissions.data', $currentPermissionsCount + 2)
                ->where('data.permissions.data.0.type', 'Permission')
                ->etc(),
        );
    }

    // TODO: move to request test
    public function testGivenUserHasNoAccessPreventsOperation(): void
    {
        $this->actingAs(User::factory()->user()->createOne());

        $response = $this->postJson(action([UserPermissionsCRUDController::class, 'assign'], ['user_id' => User::factory()->createOne()->id]));

        $response->assertForbidden();
    }
}
