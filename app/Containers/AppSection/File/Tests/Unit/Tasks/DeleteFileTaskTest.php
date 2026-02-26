<?php

namespace App\Containers\AppSection\File\Tests\Unit\Tasks;

use Apiato\Core\Repositories\Exceptions\ResourceNotFound;
use App\Containers\AppSection\File\Models\File;
use App\Containers\AppSection\File\Tasks\DeleteFileTask;
use App\Containers\AppSection\File\Tests\UnitTestCase;
use App\Containers\AppSection\User\Models\User;
use App\Ship\Parents\Exceptions\DBCallException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DeleteFileTask::class)]
final class DeleteFileTaskTest extends UnitTestCase
{
    public function testDeleteFileSoftDelete(): void
    {
        $user = User::factory()->createOne();
        $file = File::factory()->createOne([
            'uploaded_by' => $user->id,
        ]);
        $fileId = $file->id;

        $task = app(DeleteFileTask::class);
        $result = $task->run($fileId, false);

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

    public function testDeleteFileHardDelete(): void
    {
        $user = User::factory()->createOne();
        $file = File::factory()->createOne([
            'uploaded_by' => $user->id,
        ]);
        $fileId = $file->id;

        $task = app(DeleteFileTask::class);
        $result = $task->run($fileId, true);

        $this->assertTrue($result);
        // Verify it's permanently deleted from database
        $this->assertDatabaseMissing('files', [
            'id' => $fileId,
        ]);
        // Verify we cannot access it even with withTrashed
        $deletedFile = File::withTrashed()->find($fileId);
        $this->assertNull($deletedFile);
    }

    public function testHardDeleteDoesNotAffectOtherRecords(): void
    {
        $user = User::factory()->createOne();
        $file1 = File::factory()->createOne([
            'uploaded_by' => $user->id,
            'original_name' => 'file1.pdf',
        ]);
        $file2 = File::factory()->createOne([
            'uploaded_by' => $user->id,
            'original_name' => 'file2.pdf',
        ]);

        $task = app(DeleteFileTask::class);
        $result = $task->run($file1->id, true);

        $this->assertTrue($result);
        // Verify first file is permanently deleted
        $this->assertDatabaseMissing('files', [
            'id' => $file1->id,
        ]);
        // Verify second file still exists
        $this->assertDatabaseHas('files', [
            'id' => $file2->id,
            'original_name' => 'file2.pdf',
        ]);
        $this->assertNull(File::find($file2->id)->deleted_at);
    }

    public function testHardDeleteNonExistentFile(): void
    {
        $this->expectException(ResourceNotFound::class);
        $this->expectExceptionMessage('File not found');

        $task = app(DeleteFileTask::class);
        $task->run('00000000-0000-0000-0000-000000000000', true);
    }

    public function testSoftDeleteNonExistentFile(): void
    {
        $this->expectException(ResourceNotFound::class);

        $task = app(DeleteFileTask::class);
        $task->run('00000000-0000-0000-0000-000000000000', false);
    }

    public function testHardDeleteWithSoftDeletedFileThrowsResourceNotFound(): void
    {
        $user = User::factory()->createOne();
        $file = File::factory()->createOne([
            'uploaded_by' => $user->id,
        ]);
        $fileId = $file->id;

        // First soft delete the file
        $file->delete();
        $this->assertSoftDeleted('files', ['id' => $fileId]);

        // Then try to hard delete it - should throw ResourceNotFound
        // because findOrFail doesn't find soft-deleted records
        $this->expectException(ResourceNotFound::class);
        $this->expectExceptionMessage('File not found');

        $task = app(DeleteFileTask::class);
        $task->run($fileId, true);
    }

    public function testHardDeleteDefaultParameter(): void
    {
        $user = User::factory()->createOne();
        $file = File::factory()->createOne([
            'uploaded_by' => $user->id,
        ]);
        $fileId = $file->id;

        // Test that default parameter (false) results in soft delete
        $task = app(DeleteFileTask::class);
        $result = $task->run($fileId);

        $this->assertTrue($result);
        // Verify it's soft deleted (default behavior)
        $this->assertSoftDeleted('files', ['id' => $fileId]);
        $this->assertDatabaseHas('files', [
            'id' => $fileId,
        ]);
    }
}
