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
final class SyncRolePermissionsTest extends ApiTestCase
{
    public function testSyncDuplicatedPermissionsToRole(): void
    {
        $this->actingAs(User::factory()->superAdmin()->createOne());
        $permissionA = Permission::factory()->createOne();
        sleep(1);
        $permissionB = Permission::factory()->createOne();
        $role = Role::factory()->createOne();
        $role->givePermissionTo($permissionA);
        $data = [
            'permission_ids' => [$permissionA->id, $permissionB->id],
        ];

        $response = $this->putJson(action(
            [RolePermissionsCRUDController::class, 'sync'],
            ['role_id' => $role->id],
        ), $data);

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json): AssertableJson => $json->has('data')
                ->where('data.type', 'Role')
                ->where('data.id', $role->id)
                ->count('data.permissions.data', 2)
                ->etc(),
        );
    }

    // TODO: move to request test
    public function testGivenUserHasNoAccessPreventsOperation(): void
    {
        $this->actingAs(User::factory()->user()->createOne());

        $response = $this->putJson(action(
            [RolePermissionsCRUDController::class, 'sync'],
            ['role_id' => Role::factory()->createOne()->id],
        ));

        $response->assertForbidden();
    }
}
