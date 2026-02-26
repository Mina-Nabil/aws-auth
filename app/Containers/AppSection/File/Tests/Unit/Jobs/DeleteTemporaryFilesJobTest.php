<?php

namespace App\Containers\AppSection\File\Tests\Unit\Jobs;

use App\Containers\AppSection\File\Jobs\DeleteTemporaryFilesJob;
use App\Containers\AppSection\File\Models\File;
use App\Containers\AppSection\File\Tests\UnitTestCase;
use App\Containers\AppSection\User\Models\User;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DeleteTemporaryFilesJob::class)]
final class DeleteTemporaryFilesJobTest extends UnitTestCase
{
    public function testDeletesAllTemporaryFiles(): void
    {
        $user = User::factory()->createOne();

        // Create temporary files
        $tmpFile1 = File::factory()->createOne([
            'uploaded_by' => $user->id,
            'is_tmp' => true,
            'original_name' => 'tmp-file-1.pdf',
        ]);

        $tmpFile2 = File::factory()->createOne([
            'uploaded_by' => $user->id,
            'is_tmp' => true,
            'original_name' => 'tmp-file-2.csv',
        ]);

        $tmpFile3 = File::factory()->createOne([
            'uploaded_by' => $user->id,
            'is_tmp' => true,
            'original_name' => 'tmp-file-3.xlsx',
        ]);

        // Create non-temporary files (should not be deleted)
        $permanentFile1 = File::factory()->createOne([
            'uploaded_by' => $user->id,
            'is_tmp' => false,
            'original_name' => 'permanent-file-1.pdf',
        ]);

        $permanentFile2 = File::factory()->createOne([
            'uploaded_by' => $user->id,
            'is_tmp' => false,
            'original_name' => 'permanent-file-2.pdf',
        ]);

        $job = new DeleteTemporaryFilesJob();
        $job->handle(
            app(\App\Containers\AppSection\File\Tasks\ListFilesTask::class),
            app(\App\Containers\AppSection\File\Tasks\DeleteFileTask::class)
        );

        // Assert temporary files are permanently deleted (hard deleted)
        $this->assertDatabaseMissing('files', ['id' => $tmpFile1->id]);
        $this->assertDatabaseMissing('files', ['id' => $tmpFile2->id]);
        $this->assertDatabaseMissing('files', ['id' => $tmpFile3->id]);

        // Assert permanent files still exist
        $this->assertDatabaseHas('files', [
            'id' => $permanentFile1->id,
            'is_tmp' => false,
        ]);
        $this->assertDatabaseHas('files', [
            'id' => $permanentFile2->id,
            'is_tmp' => false,
        ]);
    }

    public function testDoesNotDeleteNonTemporaryFiles(): void
    {
        $user = User::factory()->createOne();

        // Create only non-temporary files
        $permanentFile1 = File::factory()->createOne([
            'uploaded_by' => $user->id,
            'is_tmp' => false,
            'original_name' => 'permanent-file-1.pdf',
        ]);

        $permanentFile2 = File::factory()->createOne([
            'uploaded_by' => $user->id,
            'is_tmp' => false,
            'original_name' => 'permanent-file-2.pdf',
        ]);

        $job = new DeleteTemporaryFilesJob();
        $job->handle(
            app(\App\Containers\AppSection\File\Tasks\ListFilesTask::class),
            app(\App\Containers\AppSection\File\Tasks\DeleteFileTask::class)
        );

        // Assert permanent files still exist
        $this->assertDatabaseHas('files', [
            'id' => $permanentFile1->id,
            'is_tmp' => false,
        ]);
        $this->assertDatabaseHas('files', [
            'id' => $permanentFile2->id,
            'is_tmp' => false,
        ]);
    }

    public function testHandlesEmptyTemporaryFilesList(): void
    {
        $user = User::factory()->createOne();

        // Create only non-temporary files
        $permanentFile = File::factory()->createOne([
            'uploaded_by' => $user->id,
            'is_tmp' => false,
            'original_name' => 'permanent-file.pdf',
        ]);

        $job = new DeleteTemporaryFilesJob();
        $job->handle(
            app(\App\Containers\AppSection\File\Tasks\ListFilesTask::class),
            app(\App\Containers\AppSection\File\Tasks\DeleteFileTask::class)
        );

        // Assert permanent file still exists
        $this->assertDatabaseHas('files', [
            'id' => $permanentFile->id,
        ]);
    }


    public function testDeletesOnlyTemporaryFilesWithMixedFiles(): void
    {
        $user = User::factory()->createOne();

        // Create mixed files
        $tmpFile1 = File::factory()->createOne([
            'uploaded_by' => $user->id,
            'is_tmp' => true,
            'original_name' => 'tmp-1.pdf',
        ]);

        $tmpFile2 = File::factory()->createOne([
            'uploaded_by' => $user->id,
            'is_tmp' => true,
            'original_name' => 'tmp-2.pdf',
        ]);

        $permanentFile1 = File::factory()->createOne([
            'uploaded_by' => $user->id,
            'is_tmp' => false,
            'original_name' => 'permanent-1.pdf',
        ]);

        $permanentFile2 = File::factory()->createOne([
            'uploaded_by' => $user->id,
            'is_tmp' => false,
            'original_name' => 'permanent-2.pdf',
        ]);

        $job = new DeleteTemporaryFilesJob();
        $job->handle(
            app(\App\Containers\AppSection\File\Tasks\ListFilesTask::class),
            app(\App\Containers\AppSection\File\Tasks\DeleteFileTask::class)
        );

        // Temporary files should be deleted
        $this->assertDatabaseMissing('files', ['id' => $tmpFile1->id]);
        $this->assertDatabaseMissing('files', ['id' => $tmpFile2->id]);

        // Permanent files should remain
        $this->assertDatabaseHas('files', ['id' => $permanentFile1->id]);
        $this->assertDatabaseHas('files', ['id' => $permanentFile2->id]);
    }

    public function testHardDeletesTemporaryFiles(): void
    {
        $user = User::factory()->createOne();

        // Create temporary file
        $tmpFile = File::factory()->createOne([
            'uploaded_by' => $user->id,
            'is_tmp' => true,
            'original_name' => 'tmp-file.pdf',
        ]);

        $fileId = $tmpFile->id;

        $job = new DeleteTemporaryFilesJob();
        $job->handle(
            app(\App\Containers\AppSection\File\Tasks\ListFilesTask::class),
            app(\App\Containers\AppSection\File\Tasks\DeleteFileTask::class)
        );

        // Verify it's hard deleted (not soft deleted)
        $this->assertDatabaseMissing('files', ['id' => $fileId]);

        // Verify it cannot be found even with withTrashed
        $deletedFile = File::withTrashed()->find($fileId);
        $this->assertNull($deletedFile);
    }
}
