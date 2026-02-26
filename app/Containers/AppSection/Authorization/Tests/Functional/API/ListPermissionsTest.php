<?php

namespace App\Containers\AppSection\Authorization\Tests\Functional\API;

use App\Containers\AppSection\Authorization\Actions\ListPermissionsAction;
use App\Containers\AppSection\Authorization\Models\Permission;
use App\Containers\AppSection\Authorization\Tests\Functional\ApiTestCase;
use App\Containers\AppSection\Authorization\API\Controllers\PermissionsCRUDController;
use App\Containers\AppSection\User\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(PermissionsCRUDController::class)]
final class ListPermissionsTest extends ApiTestCase
{
    public function testListPermissions(): void
    {
        $this->actingAs(User::factory()->superAdmin()->createOne());
        $action = app(ListPermissionsAction::class);
        $seededPermissionsCount = $action->run()->count();

        if ($seededPermissionsCount === 0) {
            $this->markTestSkipped('No permissions found');
            return;
        }

        $response = $this->getJson(action([PermissionsCRUDController::class, 'index']));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has(
                'data',
                min(10, $seededPermissionsCount),
            )->etc(),
        );
    }

    // TODO: move to request test
    public function testGivenUserHasNoAccessPreventsOperation(): void
    {
        $this->actingAs(User::factory()->user()->createOne());

        $response = $this->getJson(action([PermissionsCRUDController::class, 'index']));

        $response->assertForbidden();
    }
}
