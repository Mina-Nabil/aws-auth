<?php

namespace App\Containers\AppSection\File\Tests\Functional\API;

use App\Containers\AppSection\File\API\Controllers\FileCRUDController;
use App\Containers\AppSection\File\Enums\FileVisibility;
use App\Containers\AppSection\File\Models\File;
use App\Containers\AppSection\File\Tests\Functional\ApiTestCase;
use App\Containers\AppSection\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FileCRUDController::class)]
final class UploadFileTest extends ApiTestCase
{
    public function testCanUploadFile(): void
    {
        Storage::fake('s3');

        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $data = [
            'file' => $file,
            'description' => 'Test file description',
            'tags' => ['test', 'document'],
            'visibility' => FileVisibility::PRIVATE,
            'uploaded_by' => $user->id,
        ];

        $response = $this->postJson(action([FileCRUDController::class, 'create']), $data);

        $response->assertCreated();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has(
                'data',
                static fn (AssertableJson $json) => $json->where('description', $data['description'])
                ->etc(),
            )->etc(),
        );
    }

    // public function testGivenUserHasNoAccessPreventsOperation(): void
    // {
    //     Storage::fake('s3');

    //     $user = User::factory()->user()->createOne();

    //     $this->actingAs($user);

    //     $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    //     $response = $this->postJson(action([FileCRUDController::class, 'create']), [
    //         'file' => $file,
    //         'visibility' => File::VISIBILITY_PRIVATE,
    //         'uploaded_by' => $user->id,
    //     ]);

    //     $response->assertForbidden();
    // }

    public function testInvalidMimeTypeReturns422(): void
    {
        Storage::fake('s3');

        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        // Create a file with an invalid MIME type (e.g., .exe file)
        $file = UploadedFile::fake()->create('malicious.exe', 100, 'application/x-msdownload');

        $data = [
            'file' => $file,
            'description' => 'Test file with invalid mime type',
            'visibility' => FileVisibility::PRIVATE,
            'uploaded_by' => $user->id,
        ];

        $response = $this->postJson(action([FileCRUDController::class, 'create']), $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    public function testFileTooLargeReturns422(): void
    {
        Storage::fake('s3');

        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $maxFileSize = Config::get('appSection-file.max_file_size');

        // Create a file larger than the maximum allowed size
        $file = UploadedFile::fake()->create('large-document.pdf', $maxFileSize + 1000, 'application/pdf');

        $data = [
            'file' => $file,
            'description' => 'Test file that is too large',
            'visibility' => FileVisibility::PRIVATE,
            'uploaded_by' => $user->id,
        ];

        $response = $this->postJson(action([FileCRUDController::class, 'create']), $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    public function testInvalidVisibilityReturns422(): void
    {
        Storage::fake('s3');

        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $data = [
            'file' => $file,
            'description' => 'Test file with invalid visibility',
            'visibility' => 'invalid_visibility_value',
            'uploaded_by' => $user->id,
        ];

        $response = $this->postJson(action([FileCRUDController::class, 'create']), $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['visibility']);
    }

    public function testMissingRequiredFileReturns422(): void
    {
        Storage::fake('s3');

        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $data = [
            'description' => 'Test without file',
            'visibility' => FileVisibility::PRIVATE,
            'uploaded_by' => $user->id,
        ];

        $response = $this->postJson(action([FileCRUDController::class, 'create']), $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    public function testMissingVisibilityReturns422(): void
    {
        Storage::fake('s3');

        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $data = [
            'file' => $file,
            'description' => 'Test without visibility',
            'uploaded_by' => $user->id,
        ];

        $response = $this->postJson(action([FileCRUDController::class, 'create']), $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['visibility']);
    }

    public function testInvalidUploadedByReturns422(): void
    {
        Storage::fake('s3');

        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        // Use a non-existent user ID
        $data = [
            'file' => $file,
            'description' => 'Test file with invalid uploader',
            'visibility' => FileVisibility::PRIVATE,
            'uploaded_by' => '00000000-0000-0000-0000-000000000000',
        ];

        $response = $this->postJson(action([FileCRUDController::class, 'create']), $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['uploaded_by']);
    }

    public function testInvalidUploadedByFormatReturns422(): void
    {
        Storage::fake('s3');

        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        // Use an invalid UUID format
        $data = [
            'file' => $file,
            'description' => 'Test file with invalid UUID format',
            'visibility' => FileVisibility::PRIVATE,
            'uploaded_by' => 'not-a-valid-uuid',
        ];

        $response = $this->postJson(action([FileCRUDController::class, 'create']), $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['uploaded_by']);
    }

    public function testInvalidTagsFormatReturns422(): void
    {
        Storage::fake('s3');

        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        // Tags should be an array, not a string (after prepareForValidation)
        $data = [
            'file' => $file,
            'description' => 'Test file with invalid tags',
            'visibility' => FileVisibility::PRIVATE,
            'uploaded_by' => $user->id,
            'tags' => ['test', 123, null], // Invalid tag values
        ];

        $response = $this->postJson(action([FileCRUDController::class, 'create']), $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['tags.1', 'tags.2']);
    }

    public function testDescriptionTooLongReturns422(): void
    {
        Storage::fake('s3');

        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        // Description exceeds 255 characters
        $data = [
            'file' => $file,
            'description' => str_repeat('a', 256),
            'visibility' => FileVisibility::PRIVATE,
            'uploaded_by' => $user->id,
        ];

        $response = $this->postJson(action([FileCRUDController::class, 'create']), $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['description']);
    }
}
