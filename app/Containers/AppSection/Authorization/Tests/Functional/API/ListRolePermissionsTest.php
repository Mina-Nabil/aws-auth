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
final class ListRolePermissionsTest extends ApiTestCase
{
    public function testGetRolePermissions(): void
    {
        $this->actingAs(User::factory()->superAdmin()->createOne());
        $role = Role::factory()->createOne();
        $permission = Permission::factory()->createOne();
        $role->givePermissionTo([$permission]);

        $response = $this->getJson(action(
            [RolePermissionsCRUDController::class, 'index'],
            ['role_id' => $role->id],
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
            [RolePermissionsCRUDController::class, 'index'],
            ['role_id' => Role::factory()->createOne()->id],
        ));

        $response->assertForbidden();
    }
}
