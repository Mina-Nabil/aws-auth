<?php

namespace App\Containers\AppSection\File\Tests\Unit\Actions;

use App\Containers\AppSection\File\Actions\UpdateFileAction;
use App\Containers\AppSection\File\Data\DTOs\FileDTO;
use App\Containers\AppSection\File\Enums\FileVisibility;
use App\Containers\AppSection\File\Models\File;
use App\Containers\AppSection\File\Tests\UnitTestCase;
use App\Containers\AppSection\User\Models\User;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UpdateFileAction::class)]
final class UpdateFileActionTest extends UnitTestCase
{
    public function testCanUpdateFile(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $file = File::factory()->createOne(['uploaded_by' => $user->id]);

        $fileDTO = FileDTO::fromArray([
            'file_id' => $file->id,
            'description' => 'Updated description',
            'tags' => ['updated', 'test'],
            'visibility' => FileVisibility::PUBLIC,
        ]);

        $action = app(UpdateFileAction::class);
        $result = $action->run($fileDTO);

        $this->assertInstanceOf(File::class, $result);
        $this->assertSame('Updated description', $result->description);
        $this->assertSame(FileVisibility::PUBLIC->value, $result->visibility);
    }
}
