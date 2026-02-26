<?php

namespace App\Containers\AppSection\File\Tests\Unit\Actions;

use App\Containers\AppSection\File\Actions\ListEntityFilesAction;
use App\Containers\AppSection\File\Enums\FileType;
use App\Containers\AppSection\File\Models\File;
use App\Containers\AppSection\File\Models\FileLink;
use App\Containers\AppSection\File\Tests\UnitTestCase;
use App\Containers\AppSection\User\Models\User;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ListEntityFilesAction::class)]
final class ListEntityFilesActionTest extends UnitTestCase
{
    public function testCanListEntityFiles(): void
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

        $action = app(ListEntityFilesAction::class);
        $result = $action->run('user', $targetUser->id);

        $this->assertGreaterThanOrEqual(2, $result->total());
    }

    public function testListEntityFilesWithNoFiles(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $targetUser = User::factory()->createOne();

        // Don't create any file links for this entity

        $action = app(ListEntityFilesAction::class);
        $result = $action->run('user', $targetUser->id);

        $this->assertSame(0, $result->total());
    }

    public function testListEntityFilesWithMultipleFiles(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $targetUser = User::factory()->createOne();
        $files = File::factory()->count(10)->create(['uploaded_by' => $user->id]);

        foreach ($files as $file) {
            FileLink::factory()->create([
                'file_id' => $file->id,
                'entity_type' => 'user',
                'entity_id' => $targetUser->id,
            ]);
        }

        $action = app(ListEntityFilesAction::class);
        $result = $action->run('user', $targetUser->id);

        $this->assertSame(10, $result->total());
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

        // Create non-image file
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

        $action = app(ListEntityFilesAction::class);
        $result = $action->run('user', $targetUser->id, FileType::IMAGE->value);

        $this->assertSame(2, $result->total());
        foreach ($result as $file) {
            $this->assertContains($file->mime_type, FileType::IMAGE_MIME_TYPES);
        }
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

        // Create non-video file
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

        $action = app(ListEntityFilesAction::class);
        $result = $action->run('user', $targetUser->id, FileType::VIDEO->value);

        $this->assertSame(2, $result->total());
        foreach ($result as $file) {
            $this->assertContains($file->mime_type, FileType::VIDEO_MIME_TYPES);
        }
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

        // Create non-PDF file
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

        $action = app(ListEntityFilesAction::class);
        $result = $action->run('user', $targetUser->id, FileType::PDF->value);

        $this->assertSame(2, $result->total());
        foreach ($result as $file) {
            $this->assertContains($file->mime_type, FileType::PDF_MIME_TYPES);
        }
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

        // Create non-archive file
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

        $action = app(ListEntityFilesAction::class);
        $result = $action->run('user', $targetUser->id, FileType::ARCHIVE->value);

        $this->assertSame(2, $result->total());
        foreach ($result as $file) {
            $this->assertContains($file->mime_type, FileType::ARCHIVE_MIME_TYPES);
        }
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

        // Create non-document file
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

        $action = app(ListEntityFilesAction::class);
        $result = $action->run('user', $targetUser->id, FileType::DOCUMENT->value);

        $this->assertSame(2, $result->total());
        foreach ($result as $file) {
            $this->assertContains($file->mime_type, FileType::DOCUMENT_MIME_TYPES);
        }
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

        $action = app(ListEntityFilesAction::class);
        $result = $action->run('user', $targetUser->id);

        $this->assertSame(3, $result->total());
    }

    public function testListEntityFilesWithNullTypeReturnsAllFiles(): void
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

        $action = app(ListEntityFilesAction::class);
        $result = $action->run('user', $targetUser->id, null);

        $this->assertSame(2, $result->total());
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
        $action = app(ListEntityFilesAction::class);
        $result = $action->run('user', $targetUser->id, FileType::IMAGE->value);

        $this->assertSame(1, $result->total());
        $firstFile = $result->first();
        $this->assertNotNull($firstFile);
        $this->assertSame('image/jpeg', $firstFile->mime_type);
    }
}
