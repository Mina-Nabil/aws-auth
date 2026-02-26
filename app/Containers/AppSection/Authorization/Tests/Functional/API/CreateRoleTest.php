<?php

namespace App\Containers\AppSection\Authorization\Tests\Functional\API;

use App\Containers\AppSection\Authorization\Tests\Functional\ApiTestCase;
use App\Containers\AppSection\Authorization\API\Controllers\RoleCRUDController;
use App\Containers\AppSection\User\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(RoleCRUDController::class)]
final class CreateRoleTest extends ApiTestCase
{
    public function testCreateRole(): void
    {
        $this->actingAs(User::factory()->superAdmin()->createOne());

        $data = [
            'name' => 'manager',
            'display_name' => 'manager',
            'description' => 'he manages things',
        ];

        $response = $this->postJson(action([RoleCRUDController::class, 'create']), $data);

        $response->assertCreated();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has(
                'data',
                static fn (AssertableJson $json) => $json->where('name', $data['name'])
                ->etc(),
            )->etc(),
        );
    }

    // TODO: move to request test
    public function testGivenUserHasNoAccessPreventsOperation(): void
    {
        $this->actingAs(User::factory()->user()->createOne());

        $response = $this->postJson(action([RoleCRUDController::class, 'create']));

        $response->assertForbidden();
    }
}
