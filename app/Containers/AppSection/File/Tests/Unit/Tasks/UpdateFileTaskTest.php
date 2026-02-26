<?php

namespace App\Containers\AppSection\File\Tests\Unit\Tasks;

use App\Containers\AppSection\File\Data\DTOs\FileDTO;
use App\Containers\AppSection\File\Enums\FileVisibility;
use App\Containers\AppSection\File\Models\File;
use App\Containers\AppSection\File\Tasks\UpdateFileTask;
use App\Containers\AppSection\File\Tests\UnitTestCase;
use App\Containers\AppSection\User\Models\User;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UpdateFileTask::class)]
final class UpdateFileTaskTest extends UnitTestCase
{
    public function testUpdateFile(): void
    {
        $user = User::factory()->createOne();
        $file = File::factory()->createOne(['uploaded_by' => $user->id]);

        $fileDTO = FileDTO::fromArray([
            'file_id' => $file->id,
            'description' => 'Updated description',
            'tags' => ['updated'],
            'visibility' => FileVisibility::PUBLIC,
        ]);

        $task = app(UpdateFileTask::class);
        $result = $task->run($fileDTO);

        $this->assertInstanceOf(File::class, $result);
        $this->assertSame('Updated description', $result->description);
        $this->assertSame(FileVisibility::PUBLIC->value, $result->visibility);
    }

    public function testUpdateFileDescriptionOnly(): void
    {
        $user = User::factory()->createOne();
        $file = File::factory()->createOne([
            'uploaded_by' => $user->id,
            'description' => 'Original description',
            'tags' => ['original'],
            'visibility' => FileVisibility::PRIVATE,
        ]);

        $fileDTO = FileDTO::fromArray([
            'file_id' => $file->id,
            'description' => 'Updated description only',
        ]);

        $task = app(UpdateFileTask::class);
        $result = $task->run($fileDTO);

        $this->assertInstanceOf(File::class, $result);
        $this->assertSame('Updated description only', $result->description);
        // Tags and visibility should remain unchanged
        $this->assertSame(['original'], $result->tags);
        $this->assertSame(FileVisibility::PRIVATE->value, $result->visibility);
    }

    public function testUpdateFileTagsOnly(): void
    {
        $user = User::factory()->createOne();
        $file = File::factory()->createOne([
            'uploaded_by' => $user->id,
            'description' => 'Original description',
            'tags' => ['original'],
            'visibility' => FileVisibility::PRIVATE,
        ]);

        $fileDTO = FileDTO::fromArray([
            'file_id' => $file->id,
            'tags' => ['new', 'tags', 'only'],
        ]);

        $task = app(UpdateFileTask::class);
        $result = $task->run($fileDTO);

        $this->assertInstanceOf(File::class, $result);
        $this->assertSame(['new', 'tags', 'only'], $result->tags);
        // Description and visibility should remain unchanged
        $this->assertSame('Original description', $result->description);
        $this->assertSame(FileVisibility::PRIVATE->value, $result->visibility);
    }

    public function testUpdateFileVisibilityOnly(): void
    {
        $user = User::factory()->createOne();
        $file = File::factory()->createOne([
            'uploaded_by' => $user->id,
            'description' => 'Original description',
            'tags' => ['original'],
            'visibility' => FileVisibility::PRIVATE,
        ]);

        $fileDTO = FileDTO::fromArray([
            'file_id' => $file->id,
            'visibility' => FileVisibility::PUBLIC,
        ]);

        $task = app(UpdateFileTask::class);
        $result = $task->run($fileDTO);

        $this->assertInstanceOf(File::class, $result);
        $this->assertSame(FileVisibility::PUBLIC->value, $result->visibility);
        // Description and tags should remain unchanged
        $this->assertSame('Original description', $result->description);
        $this->assertSame(['original'], $result->tags);
    }

    public function testUpdateFileAllFields(): void
    {
        $user = User::factory()->createOne();
        $file = File::factory()->createOne([
            'uploaded_by' => $user->id,
            'description' => 'Original description',
            'tags' => ['original'],
            'visibility' => FileVisibility::PRIVATE,
        ]);

        $fileDTO = FileDTO::fromArray([
            'file_id' => $file->id,
            'description' => 'All fields updated',
            'tags' => ['all', 'new', 'tags'],
            'visibility' => FileVisibility::SHARE_LINK,
        ]);

        $task = app(UpdateFileTask::class);
        $result = $task->run($fileDTO);

        $this->assertInstanceOf(File::class, $result);
        $this->assertSame('All fields updated', $result->description);
        $this->assertSame(['all', 'new', 'tags'], $result->tags);
        $this->assertSame(FileVisibility::SHARE_LINK->value, $result->visibility);
    }

    public function testUpdateNonExistentFile(): void
    {
        $fileDTO = FileDTO::fromArray([
            'file_id' => '99999999-9999-9999-9999-999999999999',
            'description' => 'This should fail',
        ]);

        $task = app(UpdateFileTask::class);

        $this->expectException(\Apiato\Core\Repositories\Exceptions\ResourceNotFound::class);

        $task->run($fileDTO);
    }
}
