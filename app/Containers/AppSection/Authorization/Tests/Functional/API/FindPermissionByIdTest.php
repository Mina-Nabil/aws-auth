<?php

namespace App\Containers\AppSection\Authorization\Tests\Functional\API;

use App\Containers\AppSection\Authorization\Models\Permission;
use App\Containers\AppSection\Authorization\Tests\Functional\ApiTestCase;
use App\Containers\AppSection\Authorization\API\Controllers\PermissionsCRUDController;
use App\Containers\AppSection\User\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(PermissionsCRUDController::class)]
final class FindPermissionByIdTest extends ApiTestCase
{
    public function testCanFindPermissionById(): void
    {
        $this->actingAs(User::factory()->superAdmin()->createOne());
        $permission = Permission::factory()->createOne();

        $response = $this->getJson(action(
            [PermissionsCRUDController::class, 'show'],
            ['permission_id' => $permission->id],
        ));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has(
                'data',
                static fn (AssertableJson $json) => $json->where('name', $permission->name)
                    ->etc(),
            )->etc(),
        );
    }

    // TODO: move to request test
    public function testGivenUserHasNoAccessPreventsOperation(): void
    {
        $this->actingAs(User::factory()->user()->createOne());

        $response = $this->getJson(action(
            [PermissionsCRUDController::class, 'show'],
            ['permission_id' => Permission::factory()->createOne()->id],
        ));

        $response->assertForbidden();
    }
}
