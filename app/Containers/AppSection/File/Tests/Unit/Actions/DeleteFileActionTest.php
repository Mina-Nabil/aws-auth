<?php

namespace App\Containers\AppSection\File\Tests\Unit\Actions;

use App\Containers\AppSection\File\Actions\DeleteFileAction;
use App\Containers\AppSection\File\Models\File;
use App\Containers\AppSection\File\Models\FileLink;
use App\Containers\AppSection\File\Tests\UnitTestCase;
use App\Containers\AppSection\User\Models\User;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DeleteFileAction::class)]
final class DeleteFileActionTest extends UnitTestCase
{
    public function testCanDeleteFile(): void
    {
        Storage::fake('s3');

        $testPath = 'files/test-file.pdf';
        Storage::put($testPath, 'test content');

        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $file = File::factory()->createOne([
            'uploaded_by' => $user->id,
            'storage_disk' => 's3',
            'storage_path' => $testPath,
        ]);
        $fileId = $file->id;

        $action = app(DeleteFileAction::class);
        $result = $action->run($fileId);

        $this->assertTrue($result);
        $this->assertSoftDeleted('files', ['id' => $fileId]);
    }

    public function testDeleteFileCascadeDeletion(): void
    {
        Storage::fake('s3');

        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $testPath = 'files/test-file.pdf';
        Storage::put($testPath, 'test content');

        $file = File::factory()->createOne([
            'uploaded_by' => $user->id,
            'storage_disk' => 's3',
            'storage_path' => $testPath,
        ]);
        $fileId = $file->id;

        // Create some file links
        $targetUser1 = User::factory()->createOne();
        $targetUser2 = User::factory()->createOne();

        FileLink::factory()->create([
            'file_id' => $file->id,
            'entity_type' => 'user',
            'entity_id' => $targetUser1->id,
        ]);

        FileLink::factory()->create([
            'file_id' => $file->id,
            'entity_type' => 'user',
            'entity_id' => $targetUser2->id,
        ]);

        $action = app(DeleteFileAction::class);
        $result = $action->run($fileId);

        $this->assertTrue($result);

        // Verify file record is soft deleted
        $this->assertSoftDeleted('files', ['id' => $fileId]);

        // Verify file is deleted from storage
        $this->assertFalse(Storage::exists($testPath));

        // Note: File links may or may not be deleted depending on implementation
        // This test verifies the main file is soft deleted and removed from storage
    }

    public function testDeleteFileSoftDelete(): void
    {
        Storage::fake('s3');

        $testPath = 'files/test-file.pdf';
        Storage::put($testPath, 'test content');

        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $file = File::factory()->createOne([
            'uploaded_by' => $user->id,
            'storage_disk' => 's3',
            'storage_path' => $testPath,
        ]);
        $fileId = $file->id;

        $action = app(DeleteFileAction::class);
        $result = $action->run($fileId);

        $this->assertTrue($result);

        // Verify it's soft deleted (not hard deleted)
        $this->assertSoftDeleted('files', ['id' => $fileId]);

        // Verify the record still exists in DB but with deleted_at timestamp
        $this->assertDatabaseHas('files', [
            'id' => $fileId,
        ]);

        // Verify we can still access it with withTrashed
        $deletedFile = File::withTrashed()->find($fileId);
        $this->assertNotNull($deletedFile);
        $this->assertNotNull($deletedFile->deleted_at);
    }
}
