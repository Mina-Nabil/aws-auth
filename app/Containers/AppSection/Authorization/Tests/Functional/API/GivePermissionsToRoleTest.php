<?php

namespace App\Containers\AppSection\Authorization\Tests\Functional\API;

use App\Containers\AppSection\Authorization\Models\Permission;
use App\Containers\AppSection\Authorization\Models\Role;
use App\Containers\AppSection\Authorization\Tests\Functional\ApiTestCase;
use App\Containers\AppSection\Authorization\API\Controllers\RolePermissionsCRUDController;
use App\Containers\AppSection\User\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(RolePermissionsCRUDController::class)]
final class GivePermissionsToRoleTest extends ApiTestCase
{
    public function testAttachSinglePermissionToRole(): void
    {
        $this->actingAs(User::factory()->superAdmin()->createOne());
        $role = Role::factory()->createOne();
        $permission = Permission::factory()->createOne();
        $data = [
            'permission_ids' => [$permission->id],
        ];

        $response = $this->postJson(action(
            [RolePermissionsCRUDController::class, 'assign'],
            ['role_id' => $role->id],
        ), $data);

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json): AssertableJson => $json->has('data')
                ->where('data.type', 'Role')
                ->where('data.id', $role->id)
                ->has('data.permissions.data', 1)
                ->where('data.permissions.data.0.type', 'Permission')
                ->where('data.permissions.data.0.id', $permission->id)
                ->etc(),
        );
    }

    public function testAttachMultiplePermissionsToRole(): void
    {
        $this->actingAs(User::factory()->superAdmin()->createOne());
        $role = Role::factory()->createOne();
        sleep(3);
        $permissionA = Permission::factory()->createOne();
        sleep(3);
        $permissionB = Permission::factory()->createOne();
        sleep(3);
        $data = [
            'permission_ids' => [$permissionA->id, $permissionB->id],
        ];

        $response = $this->postJson(action(
            [RolePermissionsCRUDController::class, 'assign'],
            ['role_id' => $role->id],
        ), $data);

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json): AssertableJson => $json->has('data')
                ->where('data.type', 'Role')
                ->where('data.id', $role->id)
                ->has('data.permissions.data', 2)
                ->where('data.permissions.data.0.type', 'Permission')
                ->where('data.permissions.data.1.type', 'Permission')
                ->etc(),
        );
    }

    // TODO: move to request test
    public function testGivenUserHasNoAccessPreventsOperation(): void
    {
        $this->actingAs(User::factory()->user()->createOne());

        $response = $this->postJson(action(
            [RolePermissionsCRUDController::class, 'assign'],
            ['role_id' => Role::factory()->createOne()->id],
        ));

        $response->assertForbidden();
    }
}
