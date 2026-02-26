<?php

namespace App\Containers\AppSection\File\Tests\Unit\Tasks;

use App\Containers\AppSection\File\Models\File;
use App\Containers\AppSection\File\Tasks\FindFileByIdTask;
use App\Containers\AppSection\File\Tests\UnitTestCase;
use App\Containers\AppSection\User\Models\User;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FindFileByIdTask::class)]
final class FindFileByIdTaskTest extends UnitTestCase
{
    public function testFindFileById(): void
    {
        $user = User::factory()->createOne();
        $file = File::factory()->createOne(['uploaded_by' => $user->id]);

        $task = app(FindFileByIdTask::class);
        $result = $task->run($file->id);

        $this->assertInstanceOf(File::class, $result);
        $this->assertSame($file->id, $result->id);
        $this->assertSame($file->original_name, $result->original_name);
    }

    public function testFindFileByIdNotFound(): void
    {
        $task = app(FindFileByIdTask::class);

        $this->expectException(\Apiato\Core\Repositories\Exceptions\ResourceNotFound::class);

        $task->run('99999999-9999-9999-9999-999999999999');
    }

    public function testFindFileByIdSoftDeleted(): void
    {
        $user = User::factory()->createOne();
        $file = File::factory()->createOne(['uploaded_by' => $user->id]);

        // Soft delete the file
        $file->delete();

        $task = app(FindFileByIdTask::class);

        // Should throw ModelNotFoundException since soft-deleted files are excluded by default
        $this->expectException(\Apiato\Core\Repositories\Exceptions\ResourceNotFound::class);

        $task->run($file->id);
    }
}
