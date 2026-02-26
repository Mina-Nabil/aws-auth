<?php

namespace App\Containers\AppSection\File\Tests\Unit\Tasks;

use App\Containers\AppSection\File\Data\DTOs\FileDTO;
use App\Containers\AppSection\File\Enums\FileVisibility;
use App\Containers\AppSection\File\Models\File;
use App\Containers\AppSection\File\Tasks\CreateFileRecordTask;
use App\Containers\AppSection\File\Tests\UnitTestCase;
use App\Containers\AppSection\User\Models\User;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CreateFileRecordTask::class)]
final class CreateFileRecordTaskTest extends UnitTestCase
{
    public function testCreateFileRecord(): void
    {
        $user = User::factory()->createOne();

        $fakeFile = UploadedFile::fake()->create('test.pdf', 1000, 'application/pdf');

        $fileDTO = FileDTO::fromArray([
            'file' => $fakeFile,
            'checksum' => 'test-checksum',
            'visibility' => FileVisibility::PRIVATE,
            'uploaded_by' => $user->id,
            'description' => 'Test file',
            'tags' => ['test'],
        ]);

        $task = app(CreateFileRecordTask::class);
        $file = $task->run($fileDTO, 'files/test-path.pdf');

        $this->assertInstanceOf(File::class, $file);
        $this->assertSame('test.pdf', $file->original_name);
        $this->assertSame('files/test-path.pdf', $file->storage_path);
        $this->assertSame('Test file', $file->description);
    }

    public function testCreateFileRecordWithNullDescription(): void
    {
        $user = User::factory()->createOne();

        $fakeFile = UploadedFile::fake()->create('test.pdf', 1000, 'application/pdf');

        $fileDTO = FileDTO::fromArray([
            'file' => $fakeFile,
            'checksum' => 'test-checksum',
            'visibility' => FileVisibility::PRIVATE,
            'uploaded_by' => $user->id,
            'description' => null,
            'tags' => ['test'],
        ]);

        $task = app(CreateFileRecordTask::class);
        $file = $task->run($fileDTO, 'files/test-path.pdf');

        $this->assertInstanceOf(File::class, $file);
        $this->assertNull($file->description);
    }

    public function testCreateFileRecordWithEmptyTagsArray(): void
    {
        $user = User::factory()->createOne();

        $fakeFile = UploadedFile::fake()->create('test.pdf', 1000, 'application/pdf');

        $fileDTO = FileDTO::fromArray([
            'file' => $fakeFile,
            'checksum' => 'test-checksum',
            'visibility' => FileVisibility::PRIVATE,
            'uploaded_by' => $user->id,
            'description' => 'Test file',
            'tags' => [],
        ]);

        $task = app(CreateFileRecordTask::class);
        $file = $task->run($fileDTO, 'files/test-path.pdf');

        $this->assertInstanceOf(File::class, $file);
        $this->assertSame([], $file->tags);
    }

    public function testCreateFileRecordWithNullTags(): void
    {
        $user = User::factory()->createOne();

        $fakeFile = UploadedFile::fake()->create('test.pdf', 1000, 'application/pdf');

        $fileDTO = FileDTO::fromArray([
            'file' => $fakeFile,
            'checksum' => 'test-checksum',
            'visibility' => FileVisibility::PRIVATE,
            'uploaded_by' => $user->id,
            'description' => 'Test file',
            'tags' => null,
        ]);

        $task = app(CreateFileRecordTask::class);
        $file = $task->run($fileDTO, 'files/test-path.pdf');

        $this->assertInstanceOf(File::class, $file);
        $this->assertNull($file->tags);
    }

    public function testCreateFileRecordWithVeryLongFileName(): void
    {
        $user = User::factory()->createOne();

        // Create a file name that's 255 characters long
        $longFileName = str_repeat('a', 245) . '.pdf';
        $fakeFile = UploadedFile::fake()->create($longFileName, 1000, 'application/pdf');

        $fileDTO = FileDTO::fromArray([
            'file' => $fakeFile,
            'checksum' => 'test-checksum',
            'visibility' => FileVisibility::PRIVATE,
            'uploaded_by' => $user->id,
            'description' => 'Test file with long name',
            'tags' => ['test'],
        ]);

        $task = app(CreateFileRecordTask::class);
        $file = $task->run($fileDTO, 'files/test-path.pdf');

        $this->assertInstanceOf(File::class, $file);
        $this->assertSame($longFileName, $file->original_name);
    }

    public function testCreateFileRecordWithSpecialCharactersInFileName(): void
    {
        $user = User::factory()->createOne();

        $specialFileName = 'test-file_2024 (1) #special @chars & more!.pdf';
        $fakeFile = UploadedFile::fake()->create($specialFileName, 1000, 'application/pdf');

        $fileDTO = FileDTO::fromArray([
            'file' => $fakeFile,
            'checksum' => 'test-checksum',
            'visibility' => FileVisibility::PRIVATE,
            'uploaded_by' => $user->id,
            'description' => 'Test file with special chars',
            'tags' => ['test', 'special-chars'],
        ]);

        $task = app(CreateFileRecordTask::class);
        $file = $task->run($fileDTO, 'files/test-path.pdf');

        $this->assertInstanceOf(File::class, $file);
        $this->assertSame($specialFileName, $file->original_name);
    }
}
