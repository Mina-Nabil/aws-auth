<?php

namespace App\Containers\AppSection\File\Tests\Functional\API;

use App\Containers\AppSection\File\API\Controllers\FileCRUDController;
use App\Containers\AppSection\File\Enums\FileVisibility;
use App\Containers\AppSection\File\Models\File;
use App\Containers\AppSection\File\Tests\Functional\ApiTestCase;
use App\Containers\AppSection\User\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FileCRUDController::class)]
final class UpdateFileTest extends ApiTestCase
{
    public function testCanUpdateFile(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $file = File::factory()->createOne(['uploaded_by' => $user->id]);

        $data = [
            'description' => 'Updated description',
            'tags' => ['updated', 'test'],
            'visibility' => FileVisibility::PUBLIC->value,
        ];

        $response = $this->patchJson(
            action([FileCRUDController::class, 'update'], ['file_id' => $file->id]),
            $data
        );

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has(
                'data',
                static fn (AssertableJson $json) => $json->where('description', $data['description'])
                ->etc(),
            )->etc(),
        );
    }

    public function testGivenUserHasNoAccessPreventsOperation(): void
    {
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        $file = File::factory()->createOne();

        $response = $this->patchJson(
            action([FileCRUDController::class, 'update'], ['file_id' => $file->id]),
            ['description' => 'Updated', 'visibility' => FileVisibility::PUBLIC->value]
        );

        $response->assertForbidden();
    }
}
