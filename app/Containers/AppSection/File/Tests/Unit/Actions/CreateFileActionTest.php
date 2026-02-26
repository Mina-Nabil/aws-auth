<?php

namespace App\Containers\AppSection\File\Tests\Unit\Actions;

use App\Containers\AppSection\File\Actions\CreateFileAction;
use App\Containers\AppSection\File\Data\DTOs\FileDTO;
use App\Containers\AppSection\File\Enums\FileVisibility;
use App\Containers\AppSection\File\Models\File;
use App\Containers\AppSection\File\Tests\UnitTestCase;
use App\Containers\AppSection\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CreateFileAction::class)]
final class CreateFileActionTest extends UnitTestCase
{
    public function testCanCreateFile(): void
    {
        Storage::fake('s3');

        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $fileDTO = FileDTO::fromArray([
            'file' => $file,
            'description' => 'Test file',
            'tags' => ['test', 'unit'],
            'visibility' => FileVisibility::PRIVATE,
            'uploaded_by' => $user->id,
        ]);

        $action = app(CreateFileAction::class);
        $result = $action->run($fileDTO);

        $this->assertInstanceOf(File::class, $result);
        $this->assertSame('Test file', $result->description);
        $this->assertSame(FileVisibility::PRIVATE->value, $result->visibility);
    }

    // public function testCreateFileAuthorizationFailure(): void
    // {
    //     Storage::fake('s3');

    //     // Create a regular user without permission to create files
    //     $user = User::factory()->user()->createOne();
    //     $this->actingAs($user);

    //     $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    //     $fileDTO = FileDTO::fromArray([
    //         'file' => $file,
    //         'description' => 'Test file',
    //         'tags' => ['test'],
    //         'visibility' => File::VISIBILITY_PRIVATE,
    //         'uploaded_by' => $user->id,
    //     ]);

    //     $action = app(CreateFileAction::class);

    //     $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

    //     $action->run($fileDTO);
    // }

    public function testCreateFileFileManagementExceptionHandling(): void
    {
        Storage::fake('s3');

        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        // Mock Storage to throw an exception
        Storage::shouldReceive('disk->putFile')
            ->andThrow(new \Exception('Storage failure'));

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $fileDTO = FileDTO::fromArray([
            'file' => $file,
            'description' => 'Test file',
            'tags' => ['test'],
            'visibility' => FileVisibility::PRIVATE,
            'uploaded_by' => $user->id,
        ]);

        $action = app(CreateFileAction::class);

        $this->expectException(\App\Ship\Parents\Exceptions\FileManagementException::class);
        $this->expectExceptionMessage('Error uploading file');

        $action->run($fileDTO);
    }

    public function testCreateFileGenericExceptionHandling(): void
    {
        Storage::fake('s3');

        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        // Create an invalid DTO to trigger a generic exception during record creation
        $fileDTO = FileDTO::fromArray([
            'file' => $file,
            'description' => 'Test file',
            'tags' => ['test'],
            'visibility' => FileVisibility::PRIVATE,
            'uploaded_by' => '00000000-0000-0000-0000-000000000000', // Invalid user ID
        ]);

        $action = app(CreateFileAction::class);

        $this->expectException(\App\Ship\Parents\Exceptions\DBCallException::class);
        $this->expectExceptionMessage('Error creating file');

        $action->run($fileDTO);
    }
}
