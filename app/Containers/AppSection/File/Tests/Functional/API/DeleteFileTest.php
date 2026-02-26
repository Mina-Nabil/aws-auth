<?php

namespace App\Containers\AppSection\File\Tests\Functional\API;

use App\Containers\AppSection\File\API\Controllers\FileCRUDController;
use App\Containers\AppSection\File\Models\File;
use App\Containers\AppSection\File\Tests\Functional\ApiTestCase;
use App\Containers\AppSection\User\Models\User;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FileCRUDController::class)]
final class DeleteFileTest extends ApiTestCase
{
    public function testCanDeleteFile(): void
    {

        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        Storage::fake('s3');

        $testPath = 'files/test-file.pdf';
        Storage::put($testPath, 'test content');

        $file = File::factory()->createOne([
            'uploaded_by' => $user->id,
            'storage_disk' => 's3',
            'storage_path' => $testPath,
        ]);

        $response = $this->deleteJson(action(
            [FileCRUDController::class, 'destroy'],
            ['file_id' => $file->id]
        ));

        $response->assertNoContent();
    }

    public function testGivenUserHasNoAccessPreventsOperation(): void
    {
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        $file = File::factory()->createOne();

        $response = $this->deleteJson(action(
            [FileCRUDController::class, 'destroy'],
            ['file_id' => $file->id]
        ));

        $response->assertForbidden();
    }
}
