<?php

namespace App\Containers\AppSection\File\Tests\Unit\Tasks;

use App\Containers\AppSection\File\Enums\FileVisibility;
use App\Containers\AppSection\File\Models\File;
use App\Containers\AppSection\File\Tasks\UpdateFileStorageVisibilityTask;
use App\Containers\AppSection\File\Tests\UnitTestCase;
use App\Containers\AppSection\User\Models\User;
use InvalidArgumentException;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UpdateFileStorageVisibilityTask::class)]
final class UpdateFileStorageVisibilityTaskTest extends UnitTestCase
{
    public function testUpdateFileStorageVisibility(): void
    {
        Storage::fake('s3');

        $user = User::factory()->createOne();
        $testPath = 'files/test-file.pdf';

        Storage::put($testPath, 'test content');

        $file = File::factory()->createOne([
            'uploaded_by' => $user->id,
            'storage_disk' => 's3',
            'storage_path' => $testPath,
            'visibility' => FileVisibility::PRIVATE,
        ]);

        $task = app(UpdateFileStorageVisibilityTask::class);
        $result = $task->run($file, FileVisibility::PUBLIC);

        $this->assertTrue($result);
    }
}
