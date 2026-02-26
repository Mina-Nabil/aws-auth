<?php

namespace App\Containers\AppSection\File\Tests\Unit\Tasks;

use App\Containers\AppSection\File\Models\File;
use App\Containers\AppSection\File\Tasks\DeleteFileFromStorageTask;
use App\Containers\AppSection\File\Tests\UnitTestCase;
use App\Containers\AppSection\User\Models\User;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DeleteFileFromStorageTask::class)]
final class DeleteFileFromStorageTaskTest extends UnitTestCase
{
    public function testDeleteFileFromStorage(): void
    {
        Storage::fake('s3');

        $user = User::factory()->createOne();
        $testPath = 'files/test-file.pdf';

        Storage::put($testPath, 'test content');

        $file = File::factory()->createOne([
            'uploaded_by' => $user->id,
            'storage_disk' => 's3',
            'storage_path' => $testPath,
        ]);

        $task = app(DeleteFileFromStorageTask::class);
        $result = $task->run($file);

        $this->assertTrue($result);
        $this->assertFalse(Storage::exists($testPath));
    }

    public function testDeleteNonExistentFile(): void
    {
        Storage::fake('s3');

        $user = User::factory()->createOne();
        $testPath = 'files/non-existent-file.pdf';

        // Don't create the file, just the record
        $file = File::factory()->createOne([
            'uploaded_by' => $user->id,
            'storage_disk' => 's3',
            'storage_path' => $testPath,
        ]);

        $task = app(DeleteFileFromStorageTask::class);

        // Should still return true even if file doesn't exist
        $result = $task->run($file);

        $this->assertTrue($result);
        $this->assertFalse(Storage::exists($testPath));
    }

    public function testDeleteFileStorageExceptionHandling(): void
    {
        Storage::fake('s3');
        Storage::shouldReceive('delete')
            ->andThrow(new \Exception('Storage deletion error'));

        $user = User::factory()->createOne();

        $file = File::factory()->createOne([
            'uploaded_by' => $user->id,
            'storage_disk' => 's3',
            'storage_path' => 'files/test.pdf',
        ]);

        $task = app(DeleteFileFromStorageTask::class);

        $this->expectException(\App\Ship\Parents\Exceptions\FileManagementException::class);
        $this->expectExceptionMessage('Error deleting file from storage');

        $task->run($file);
    }
}
