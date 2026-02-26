<?php

namespace App\Containers\AppSection\Authorization\Tests\Functional\API;

use App\Containers\AppSection\Authorization\Models\Role;
use App\Containers\AppSection\Authorization\Tests\Functional\ApiTestCase;
use App\Containers\AppSection\Authorization\API\Controllers\RoleCRUDController;
use App\Containers\AppSection\User\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(RoleCRUDController::class)]
final class FindRoleByIdTest extends ApiTestCase
{
    public function testCanFindRoleById(): void
    {
        $this->actingAs(User::factory()->superAdmin()->createOne());
        $roleA = Role::factory()->createOne();

        $response = $this->getJson(action(
            [RoleCRUDController::class, 'show'],
            ['role_id' => $roleA->id],
        ));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has(
                'data',
                static fn (AssertableJson $json) => $json->where('name', $roleA->name)
                ->etc(),
            )->etc(),
        );
    }

    // TODO: move to request test
    public function testGivenUserHasNoAccessPreventsOperation(): void
    {
        $this->actingAs(User::factory()->user()->createOne());

        $response = $this->getJson(action(
            [RoleCRUDController::class, 'show'],
            ['role_id' => Role::factory()->createOne()->id],
        ));

        $response->assertForbidden();
    }
}
