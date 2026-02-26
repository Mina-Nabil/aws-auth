<?php

namespace App\Containers\AppSection\File\Tests\Functional\API;

use App\Containers\AppSection\File\API\Controllers\FileLinkController;
use App\Containers\AppSection\File\Models\File;
use App\Containers\AppSection\File\Models\FileLink;
use App\Containers\AppSection\File\Tests\Functional\ApiTestCase;
use App\Containers\AppSection\User\Models\User;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FileLinkController::class)]
final class DetachFileTest extends ApiTestCase
{
    public function testCanDetachFile(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $targetUser = User::factory()->createOne();
        $file = File::factory()->create(['uploaded_by' => $user->id]);

        FileLink::factory()->create([
            'file_id' => $file->id,
            'entity_type' => 'user',
            'entity_id' => $targetUser->id,
        ]);

        $response = $this->deleteJson(action(
            [FileLinkController::class, 'destroy'],
            [
                'entity_type' => 'user',
                'entity_id' => $targetUser->id,
                'file_id' => $file->id
            ]
        ));

        $response->assertNoContent();
    }

    public function testGivenUserHasNoAccessPreventsOperation(): void
    {
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        $targetUser = User::factory()->createOne();
        $file = File::factory()->createOne();

        $response = $this->deleteJson(action(
            [FileLinkController::class, 'destroy'],
            [
                'entity_type' => 'user',
                'entity_id' => $targetUser->id,
                'file_id' => $file->id
            ]
        ));

        $response->assertForbidden();
    }
}
