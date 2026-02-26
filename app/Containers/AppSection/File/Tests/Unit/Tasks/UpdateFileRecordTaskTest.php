<?php

namespace App\Containers\AppSection\File\Tests\Unit\Tasks;

use App\Containers\AppSection\File\Data\DTOs\FileDTO;
use App\Containers\AppSection\File\Enums\FileVisibility;
use App\Containers\AppSection\File\Models\File;
use App\Containers\AppSection\File\Tasks\UpdateFileRecordTask;
use App\Containers\AppSection\File\Tests\UnitTestCase;
use App\Containers\AppSection\User\Models\User;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UpdateFileRecordTask::class)]
final class UpdateFileRecordTaskTest extends UnitTestCase
{
    public function testUpdateFileRecord(): void
    {
        $user = User::factory()->createOne();
        $file = File::factory()->createOne(['uploaded_by' => $user->id]);

        $fileDTO = FileDTO::fromArray([
            'file_id' => $file->id,
            'description' => 'Updated record description',
            'tags' => ['record', 'updated'],
            'visibility' => FileVisibility::SHARE_LINK,
        ]);

        $task = app(UpdateFileRecordTask::class);
        $result = $task->run($fileDTO);

        $this->assertInstanceOf(File::class, $result);
        $this->assertSame('Updated record description', $result->description);
        $this->assertSame(FileVisibility::SHARE_LINK->value, $result->visibility);
    }
}
