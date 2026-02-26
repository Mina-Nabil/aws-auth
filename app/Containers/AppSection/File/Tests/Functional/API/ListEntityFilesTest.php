<?php

namespace App\Containers\AppSection\File\Tests\Functional\API;

use App\Containers\AppSection\File\API\Controllers\FileLinkController;
use App\Containers\AppSection\File\Enums\FileType;
use App\Containers\AppSection\File\Models\File;
use App\Containers\AppSection\File\Models\FileLink;
use App\Containers\AppSection\File\Tests\Functional\ApiTestCase;
use App\Containers\AppSection\User\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FileLinkController::class)]
final class ListEntityFilesTest extends ApiTestCase
{
    public function testListEntityFiles(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $targetUser = User::factory()->createOne();
        $files = File::factory()->count(2)->create(['uploaded_by' => $user->id]);

        foreach ($files as $file) {
            FileLink::factory()->create([
                'file_id' => $file->id,
                'entity_type' => 'user',
                'entity_id' => $targetUser->id,
            ]);
        }

        $response = $this->getJson(action(
            [FileLinkController::class, 'index'],
            ['entity_type' => 'user', 'entity_id' => $targetUser->id]
        ));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has('data', 2)->etc(),
        );
    }

    public function testGivenUserHasNoAccessPreventsOperation(): void
    {
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        $targetUser = User::factory()->createOne();

        $response = $this->getJson(action(
            [FileLinkController::class, 'index'],
            ['entity_type' => 'user', 'entity_id' => $targetUser->id]
        ));

        $response->assertForbidden();
    }

    public function testListEntityFilesWithImageTypeFilter(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $targetUser = User::factory()->createOne();

        // Create image files
        $imageFiles = [
            File::factory()->create([
                'uploaded_by' => $user->id,
                'mime_type' => 'image/jpeg',
                'original_name' => 'photo1.jpg',
            ]),
            File::factory()->create([
                'uploaded_by' => $user->id,
                'mime_type' => 'image/png',
                'original_name' => 'photo2.png',
            ]),
        ];

        // Create non-image files
        $pdfFile = File::factory()->create([
            'uploaded_by' => $user->id,
            'mime_type' => 'application/pdf',
            'original_name' => 'document.pdf',
        ]);

        foreach ($imageFiles as $file) {
            FileLink::factory()->create([
                'file_id' => $file->id,
                'entity_type' => 'user',
                'entity_id' => $targetUser->id,
            ]);
        }

        FileLink::factory()->create([
            'file_id' => $pdfFile->id,
            'entity_type' => 'user',
            'entity_id' => $targetUser->id,
        ]);

        $response = $this->getJson(action(
            [FileLinkController::class, 'index'],
            ['entity_type' => 'user', 'entity_id' => $targetUser->id, 'type' => FileType::IMAGE->value]
        ));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has('data', 2)->etc(),
        );
    }

    public function testListEntityFilesWithVideoTypeFilter(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $targetUser = User::factory()->createOne();

        // Create video files
        $videoFiles = [
            File::factory()->create([
                'uploaded_by' => $user->id,
                'mime_type' => 'video/mp4',
                'original_name' => 'video1.mp4',
            ]),
            File::factory()->create([
                'uploaded_by' => $user->id,
                'mime_type' => 'video/quicktime',
                'original_name' => 'video2.mov',
            ]),
        ];

        // Create non-video files
        $imageFile = File::factory()->create([
            'uploaded_by' => $user->id,
            'mime_type' => 'image/jpeg',
            'original_name' => 'photo.jpg',
        ]);

        foreach ($videoFiles as $file) {
            FileLink::factory()->create([
                'file_id' => $file->id,
                'entity_type' => 'user',
                'entity_id' => $targetUser->id,
            ]);
        }

        FileLink::factory()->create([
            'file_id' => $imageFile->id,
            'entity_type' => 'user',
            'entity_id' => $targetUser->id,
        ]);

        $response = $this->getJson(action(
            [FileLinkController::class, 'index'],
            ['entity_type' => 'user', 'entity_id' => $targetUser->id, 'type' => FileType::VIDEO->value]
        ));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has('data', 2)->etc(),
        );
    }

    public function testListEntityFilesWithPdfTypeFilter(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $targetUser = User::factory()->createOne();

        // Create PDF files
        $pdfFiles = [
            File::factory()->create([
                'uploaded_by' => $user->id,
                'mime_type' => 'application/pdf',
                'original_name' => 'document1.pdf',
            ]),
            File::factory()->create([
                'uploaded_by' => $user->id,
                'mime_type' => 'application/pdf',
                'original_name' => 'document2.pdf',
            ]),
        ];

        // Create non-PDF files
        $imageFile = File::factory()->create([
            'uploaded_by' => $user->id,
            'mime_type' => 'image/png',
            'original_name' => 'photo.png',
        ]);

        foreach ($pdfFiles as $file) {
            FileLink::factory()->create([
                'file_id' => $file->id,
                'entity_type' => 'user',
                'entity_id' => $targetUser->id,
            ]);
        }

        FileLink::factory()->create([
            'file_id' => $imageFile->id,
            'entity_type' => 'user',
            'entity_id' => $targetUser->id,
        ]);

        $response = $this->getJson(action(
            [FileLinkController::class, 'index'],
            ['entity_type' => 'user', 'entity_id' => $targetUser->id, 'type' => FileType::PDF->value]
        ));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has('data', 2)->etc(),
        );
    }

    public function testListEntityFilesWithArchiveTypeFilter(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $targetUser = User::factory()->createOne();

        // Create archive files
        $archiveFiles = [
            File::factory()->create([
                'uploaded_by' => $user->id,
                'mime_type' => 'application/zip',
                'original_name' => 'archive1.zip',
            ]),
            File::factory()->create([
                'uploaded_by' => $user->id,
                'mime_type' => 'application/x-rar-compressed',
                'original_name' => 'archive2.rar',
            ]),
        ];

        // Create non-archive files
        $pdfFile = File::factory()->create([
            'uploaded_by' => $user->id,
            'mime_type' => 'application/pdf',
            'original_name' => 'document.pdf',
        ]);

        foreach ($archiveFiles as $file) {
            FileLink::factory()->create([
                'file_id' => $file->id,
                'entity_type' => 'user',
                'entity_id' => $targetUser->id,
            ]);
        }

        FileLink::factory()->create([
            'file_id' => $pdfFile->id,
            'entity_type' => 'user',
            'entity_id' => $targetUser->id,
        ]);

        $response = $this->getJson(action(
            [FileLinkController::class, 'index'],
            ['entity_type' => 'user', 'entity_id' => $targetUser->id, 'type' => FileType::ARCHIVE->value]
        ));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has('data', 2)->etc(),
        );
    }

    public function testListEntityFilesWithDocumentTypeFilter(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $targetUser = User::factory()->createOne();

        // Create document files
        $documentFiles = [
            File::factory()->create([
                'uploaded_by' => $user->id,
                'mime_type' => 'application/msword',
                'original_name' => 'document1.doc',
            ]),
            File::factory()->create([
                'uploaded_by' => $user->id,
                'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'original_name' => 'document2.docx',
            ]),
        ];

        // Create non-document files
        $pdfFile = File::factory()->create([
            'uploaded_by' => $user->id,
            'mime_type' => 'application/pdf',
            'original_name' => 'document.pdf',
        ]);

        foreach ($documentFiles as $file) {
            FileLink::factory()->create([
                'file_id' => $file->id,
                'entity_type' => 'user',
                'entity_id' => $targetUser->id,
            ]);
        }

        FileLink::factory()->create([
            'file_id' => $pdfFile->id,
            'entity_type' => 'user',
            'entity_id' => $targetUser->id,
        ]);

        $response = $this->getJson(action(
            [FileLinkController::class, 'index'],
            ['entity_type' => 'user', 'entity_id' => $targetUser->id, 'type' => FileType::DOCUMENT->value]
        ));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has('data', 2)->etc(),
        );
    }

    public function testListEntityFilesWithoutTypeFilterReturnsAllFiles(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $targetUser = User::factory()->createOne();

        // Create files of different types
        $files = [
            File::factory()->create([
                'uploaded_by' => $user->id,
                'mime_type' => 'image/jpeg',
                'original_name' => 'photo.jpg',
            ]),
            File::factory()->create([
                'uploaded_by' => $user->id,
                'mime_type' => 'video/mp4',
                'original_name' => 'video.mp4',
            ]),
            File::factory()->create([
                'uploaded_by' => $user->id,
                'mime_type' => 'application/pdf',
                'original_name' => 'document.pdf',
            ]),
        ];

        foreach ($files as $file) {
            FileLink::factory()->create([
                'file_id' => $file->id,
                'entity_type' => 'user',
                'entity_id' => $targetUser->id,
            ]);
        }

        $response = $this->getJson(action(
            [FileLinkController::class, 'index'],
            ['entity_type' => 'user', 'entity_id' => $targetUser->id]
        ));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has('data', 3)->etc(),
        );
    }

    public function testListEntityFilesWithInvalidTypeReturns422(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $targetUser = User::factory()->createOne();

        $response = $this->getJson(action(
            [FileLinkController::class, 'index'],
            ['entity_type' => 'user', 'entity_id' => $targetUser->id, 'type' => 'invalid_type']
        ));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['type']);
    }

    public function testListEntityFilesWithTypeFilterExcludesNonMatchingFiles(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $targetUser = User::factory()->createOne();

        // Create mixed file types
        $imageFile = File::factory()->create([
            'uploaded_by' => $user->id,
            'mime_type' => 'image/jpeg',
            'original_name' => 'photo.jpg',
        ]);

        $videoFile = File::factory()->create([
            'uploaded_by' => $user->id,
            'mime_type' => 'video/mp4',
            'original_name' => 'video.mp4',
        ]);

        $pdfFile = File::factory()->create([
            'uploaded_by' => $user->id,
            'mime_type' => 'application/pdf',
            'original_name' => 'document.pdf',
        ]);

        FileLink::factory()->create([
            'file_id' => $imageFile->id,
            'entity_type' => 'user',
            'entity_id' => $targetUser->id,
        ]);

        FileLink::factory()->create([
            'file_id' => $videoFile->id,
            'entity_type' => 'user',
            'entity_id' => $targetUser->id,
        ]);

        FileLink::factory()->create([
            'file_id' => $pdfFile->id,
            'entity_type' => 'user',
            'entity_id' => $targetUser->id,
        ]);

        // Filter for images only
        $response = $this->getJson(action(
            [FileLinkController::class, 'index'],
            ['entity_type' => 'user', 'entity_id' => $targetUser->id, 'type' => FileType::IMAGE->value]
        ));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has('data', 1)->etc(),
        );

        // Verify the returned file is an image
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has('data', 1, function (AssertableJson $item) {
                return $item->where('mime_type', 'image/jpeg')->etc();
            })->etc(),
        );
    }
}
