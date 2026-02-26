<?php

namespace App\Containers\AppSection\File\Tests\Functional\API;

use App\Containers\AppSection\File\API\Controllers\FileLinkController;
use App\Containers\AppSection\File\Models\File;
use App\Containers\AppSection\File\Tests\Functional\ApiTestCase;
use App\Containers\AppSection\User\Models\User;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FileLinkController::class)]
final class AttachFileTest extends ApiTestCase
{
    public function testCanAttachFile(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $targetUser = User::factory()->createOne();
        $file = File::factory()->create(['uploaded_by' => $user->id]);

        $data = [
            'file_id' => $file->id,
        ];

        $response = $this->postJson(
            action(
                [FileLinkController::class, 'create'],
                ['entity_type' => 'user', 'entity_id' => $targetUser->id]
            ),
            $data
        );

        $response->assertCreated();
    }

    public function testGivenUserHasNoAccessPreventsOperation(): void
    {
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        $targetUser = User::factory()->createOne();
        $file = File::factory()->createOne();

        $response = $this->postJson(
            action(
                [FileLinkController::class, 'create'],
                ['entity_type' => 'user', 'entity_id' => $targetUser->id]
            ),
            ['file_id' => $file->id]
        );

        $response->assertForbidden();
    }
}
