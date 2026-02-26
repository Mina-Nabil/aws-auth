<?php

namespace App\Containers\AppSection\File\Tests\Unit\Actions;

use App\Containers\AppSection\File\Actions\DetachFileAction;
use App\Containers\AppSection\File\Models\File;
use App\Containers\AppSection\File\Models\FileLink;
use App\Containers\AppSection\File\Tests\UnitTestCase;
use App\Containers\AppSection\User\Models\User;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DetachFileAction::class)]
final class DetachFileActionTest extends UnitTestCase
{
    public function testCanDetachFile(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $targetUser = User::factory()->createOne();
        $file = File::factory()->createOne(['uploaded_by' => $user->id]);

        FileLink::factory()->create([
            'file_id' => $file->id,
            'entity_type' => 'user',
            'entity_id' => $targetUser->id,
        ]);

        $action = app(DetachFileAction::class);
        $result = $action->run('user', $targetUser->id, $file->id);

        $this->assertTrue($result);
        $this->assertFalse($file->fresh()->isLinkedTo($targetUser));
    }

    public function testDetachNonExistentLink(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $targetUser = User::factory()->createOne();
        $file = File::factory()->createOne(['uploaded_by' => $user->id]);

        // Don't create a link, just try to detach

        $action = app(DetachFileAction::class);

        // Should still return true even if link doesn't exist
        $result = $action->run('user', $targetUser->id, $file->id);

        $this->assertTrue($result);
    }

    public function testDetachFileWithInvalidEntityType(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $file = File::factory()->createOne(['uploaded_by' => $user->id]);

        $action = app(DetachFileAction::class);

        $this->expectException(\App\Ship\Parents\Exceptions\InvalidRequestException::class);
        $this->expectExceptionMessage('Invalid entity type: invalid_type');

        $action->run('invalid_type', $user->id, $file->id);
    }
}
