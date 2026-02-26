<?php

namespace App\Containers\AppSection\File\Tests\Unit\Actions;

use App\Containers\AppSection\File\Actions\AttachFileAction;
use App\Containers\AppSection\File\Models\File;
use App\Containers\AppSection\File\Tests\UnitTestCase;
use App\Containers\AppSection\User\Models\User;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AttachFileAction::class)]
final class AttachFileActionTest extends UnitTestCase
{
    public function testCanAttachFile(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $targetUser = User::factory()->createOne();
        $file = File::factory()->createOne(['uploaded_by' => $user->id]);

        $action = app(AttachFileAction::class);
        $result = $action->run('user', $targetUser->id, $file->id);

        $this->assertTrue($result);
        $this->assertTrue($file->isLinkedTo($targetUser));
    }

    public function testAttachFileWithInvalidEntityType(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $file = File::factory()->createOne(['uploaded_by' => $user->id]);

        $action = app(AttachFileAction::class);

        $this->expectException(\App\Ship\Parents\Exceptions\InvalidRequestException::class);
        $this->expectExceptionMessage('Invalid entity type: invalid_type');

        $action->run('invalid_type', $user->id, $file->id);
    }

    public function testAttachFileWithNonExistentEntityId(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $file = File::factory()->createOne(['uploaded_by' => $user->id]);

        $action = app(AttachFileAction::class);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $action->run('user', '99999999-9999-9999-9999-999999999999', $file->id);
    }

    public function testAttachFileAuthorizationFailure(): void
    {
        $adminUser = User::factory()->superAdmin()->createOne();
        $regularUser = User::factory()->user()->createOne();
        $this->actingAs($regularUser);

        $targetUser = User::factory()->createOne();
        $file = File::factory()->createOne(['uploaded_by' => $adminUser->id]);

        $action = app(AttachFileAction::class);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        $action->run('user', $targetUser->id, $file->id);
    }

    public function testAttachFileWithNonExistentFileId(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $targetUser = User::factory()->createOne();

        $action = app(AttachFileAction::class);

        $this->expectException(\App\Ship\Parents\Exceptions\DBCallException::class);
        $this->expectExceptionMessage('Error finding SS file');

        $action->run('user', $targetUser->id, '99999999-9999-9999-9999-999999999999');
    }

    public function testAttachFileAlreadyAttachedToSameEntity(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $targetUser = User::factory()->createOne();
        $file = File::factory()->createOne(['uploaded_by' => $user->id]);

        $action = app(AttachFileAction::class);

        // Attach the file for the first time
        $result1 = $action->run('user', $targetUser->id, $file->id);
        $this->assertTrue($result1);

        // Attach the same file again to the same entity (should not duplicate)
        $result2 = $action->run('user', $targetUser->id, $file->id);
        $this->assertTrue($result2);

        // Verify there's only one link
        $linkCount = $file->entityLinks()
            ->where('entity_id', $targetUser->id)
            ->where('entity_type', $targetUser->getMorphClass())
            ->count();

        $this->assertSame(1, $linkCount);
    }

    public function testAttachSameFileToMultipleDifferentEntities(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $targetUser1 = User::factory()->createOne();
        $targetUser2 = User::factory()->createOne();
        $file = File::factory()->createOne(['uploaded_by' => $user->id]);

        $action = app(AttachFileAction::class);

        // Attach the file to first entity
        $result1 = $action->run('user', $targetUser1->id, $file->id);
        $this->assertTrue($result1);

        // Attach the same file to second entity
        $result2 = $action->run('user', $targetUser2->id, $file->id);
        $this->assertTrue($result2);

        // Verify both links exist
        $this->assertTrue($file->isLinkedTo($targetUser1));
        $this->assertTrue($file->isLinkedTo($targetUser2));

        // Verify there are 2 links total
        $this->assertSame(2, $file->entityLinks()->count());
    }
}
