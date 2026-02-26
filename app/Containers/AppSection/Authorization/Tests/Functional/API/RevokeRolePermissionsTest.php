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
final class RevokeRolePermissionsTest extends ApiTestCase
{
    public function testDetachSinglePermissionFromRole(): void
    {
        $this->actingAs(User::factory()->superAdmin()->createOne());
        $permissionA = Permission::factory()->createOne();
        $permissionB = Permission::factory()->createOne();
        $role = Role::factory()->createOne();
        $role->givePermissionTo([$permissionA, $permissionB]);
        $data = [
            'permission_ids' => [$permissionA->id],
        ];

        $response = $this->deleteJson(action(
            [RolePermissionsCRUDController::class, 'destroy'],
            ['role_id' => $role->id],
        ), $data);

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json): AssertableJson => $json->has('data')
                ->where('data.type', 'Role')
                ->where('data.id', $role->id)
                ->count('data.permissions.data', 1)
                ->where('data.permissions.data.0.id', $permissionB->id)
                ->etc(),
        );
    }

    public function testDetachMultiplePermissionFromRole(): void
    {
        $this->actingAs(User::factory()->superAdmin()->createOne());
        $permissionA = Permission::factory()->createOne();
        $permissionB = Permission::factory()->createOne();
        $permissionC = Permission::factory()->createOne();
        $role = Role::factory()->createOne();
        $role->givePermissionTo([$permissionA, $permissionB, $permissionC]);
        $data = [
            'permission_ids' => [$permissionA->id, $permissionC->id],
        ];

        $response = $this->deleteJson(action(
            [RolePermissionsCRUDController::class, 'destroy'],
            ['role_id' => $role->id],
        ), $data);

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json): AssertableJson => $json->has('data')
                ->where('data.type', 'Role')
                ->where('data.id', $role->id)
                ->count('data.permissions.data', 1)
                ->where('data.permissions.data.0.id', $permissionB->id)
                ->etc(),
        );
    }

    // TODO: move to request test
    public function testGivenUserHasNoAccessPreventsOperation(): void
    {
        $this->actingAs(User::factory()->user()->createOne());

        $response = $this->deleteJson(action(
            [RolePermissionsCRUDController::class, 'destroy'],
            ['role_id' => Role::factory()->createOne()->id],
        ));

        $response->assertForbidden();
    }
}
