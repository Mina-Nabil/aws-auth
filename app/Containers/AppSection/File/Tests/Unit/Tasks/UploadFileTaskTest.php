<?php

namespace App\Containers\AppSection\File\Tests\Unit\Tasks;

use App\Containers\AppSection\File\Data\DTOs\FileDTO;
use App\Containers\AppSection\File\Enums\FileVisibility;
use App\Containers\AppSection\File\Tasks\UploadFileTask;
use App\Containers\AppSection\File\Tests\UnitTestCase;
use App\Containers\AppSection\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UploadFileTask::class)]
final class UploadFileTaskTest extends UnitTestCase
{
    public function testUploadFile(): void
    {
        Storage::fake('s3');

        $user = User::factory()->createOne();
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $fileDTO = FileDTO::fromArray([
            'file' => $file,
            'storage_disk' => 's3',
            'visibility' => FileVisibility::PRIVATE,
            'uploaded_by' => $user->id,
        ]);

        $task = app(UploadFileTask::class);
        $path = $task->run($fileDTO);

        $this->assertIsString($path);
        $this->assertTrue(Storage::exists($path));
    }

    public function testUploadFileWithPublicVisibility(): void
    {
        Storage::fake('s3');

        $user = User::factory()->createOne();
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $fileDTO = FileDTO::fromArray([
            'file' => $file,
            'storage_disk' => 's3',
            'visibility' => FileVisibility::PUBLIC,
            'uploaded_by' => $user->id,
        ]);

        $task = app(UploadFileTask::class);
        $path = $task->run($fileDTO);

        $this->assertIsString($path);
        $this->assertTrue(Storage::exists($path));
    }

    // Not supported on MINIO yet
    // public function testUploadFileWithShareLinkVisibility(): void
    // {
    //     Storage::fake('s3');

    //     $user = User::factory()->createOne();
    //     $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    //     $fileDTO = FileDTO::fromArray([
    //         'file' => $file,
    //         'storage_disk' => 's3',
    //         'visibility' => FileVisibility::SHARE_LINK,
    //         'uploaded_by' => $user->id,
    //     ]);

    //     $task = app(UploadFileTask::class);
    //     $path = $task->run($fileDTO);

    //     $this->assertIsString($path);
    //     $this->assertTrue(Storage::exists($path));
    // }

    public function testUploadDifferentFileTypes(): void
    {
        Storage::fake('s3');

        $user = User::factory()->createOne();

        // Test image file
        $imageFile = UploadedFile::fake()->image('photo.jpg');
        $imageDTO = FileDTO::fromArray([
            'file' => $imageFile,
            'storage_disk' => 's3',
            'visibility' => FileVisibility::PRIVATE,
            'uploaded_by' => $user->id,
        ]);

        $task = app(UploadFileTask::class);
        $imagePath = $task->run($imageDTO);
        $this->assertTrue(Storage::exists($imagePath));

        // Test PDF file
        $pdfFile = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');
        $pdfDTO = FileDTO::fromArray([
            'file' => $pdfFile,
            'storage_disk' => 's3',
            'visibility' => FileVisibility::PRIVATE,
            'uploaded_by' => $user->id,
        ]);
        $pdfPath = $task->run($pdfDTO);
        $this->assertTrue(Storage::exists($pdfPath));

        // Test video file
        $videoFile = UploadedFile::fake()->create('video.mp4', 1000, 'video/mp4');
        $videoDTO = FileDTO::fromArray([
            'file' => $videoFile,
            'storage_disk' => 's3',
            'visibility' => FileVisibility::PRIVATE,
            'uploaded_by' => $user->id,
        ]);
        $videoPath = $task->run($videoDTO);
        $this->assertTrue(Storage::exists($videoPath));

        // Test archive file
        $archiveFile = UploadedFile::fake()->create('archive.zip', 500, 'application/zip');
        $archiveDTO = FileDTO::fromArray([
            'file' => $archiveFile,
            'storage_disk' => 's3',
            'visibility' => FileVisibility::PRIVATE,
            'uploaded_by' => $user->id,
        ]);
        $archivePath = $task->run($archiveDTO);
        $this->assertTrue(Storage::exists($archivePath));
    }

    public function testUploadFileFailureThrowsException(): void
    {
        Storage::fake('s3');
        Storage::shouldReceive('disk->putFile')
            ->andReturn(false);

        $user = User::factory()->createOne();
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $fileDTO = FileDTO::fromArray([
            'file' => $file,
            'storage_disk' => 's3',
            'visibility' => FileVisibility::PRIVATE,
            'uploaded_by' => $user->id,
        ]);

        $task = app(UploadFileTask::class);

        $this->expectException(\App\Ship\Parents\Exceptions\FileManagementException::class);
        $this->expectExceptionMessage('Error uploading file');

        $task->run($fileDTO);
    }

    public function testUploadFileStorageExceptionHandling(): void
    {
        Storage::fake('s3');
        Storage::shouldReceive('disk->putFile')
            ->andThrow(new \Exception('Storage error'));

        $user = User::factory()->createOne();
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $fileDTO = FileDTO::fromArray([
            'file' => $file,
            'storage_disk' => 's3',
            'visibility' => FileVisibility::PRIVATE,
            'uploaded_by' => $user->id,
        ]);

        $task = app(UploadFileTask::class);

        $this->expectException(\App\Ship\Parents\Exceptions\FileManagementException::class);
        $this->expectExceptionMessage('Error uploading file');

        $task->run($fileDTO);
    }
}
