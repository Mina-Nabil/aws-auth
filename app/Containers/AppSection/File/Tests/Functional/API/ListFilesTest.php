<?php

namespace App\Containers\AppSection\File\Tests\Functional\API;

use App\Containers\AppSection\File\API\Controllers\FileCRUDController;
use App\Containers\AppSection\File\Models\File;
use App\Containers\AppSection\File\Models\FileLink;
use App\Containers\AppSection\File\Tests\Functional\ApiTestCase;
use App\Containers\AppSection\User\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FileCRUDController::class)]
final class ListFilesTest extends ApiTestCase
{
    public function testListFiles(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        File::factory()->count(3)->create(['uploaded_by' => $user->id]);

        $response = $this->getJson(action([FileCRUDController::class, 'index']));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has('data', 3)->etc(),
        );
    }

    public function testGivenUserHasNoAccessPreventsOperation(): void
    {
        $this->actingAs(User::factory()->user()->createOne());

        $response = $this->getJson(action([FileCRUDController::class, 'index']));

        $response->assertForbidden();
    }

    public function testFilterBySearchQuery(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        // Create files with specific names and descriptions
        File::factory()->createOne([
            'uploaded_by' => $user->id,
            'original_name' => 'project-report.pdf',
            'description' => 'Annual project report',
        ]);

        File::factory()->createOne([
            'uploaded_by' => $user->id,
            'original_name' => 'budget.xlsx',
            'description' => 'Monthly budget spreadsheet',
        ]);

        File::factory()->createOne([
            'uploaded_by' => $user->id,
            'original_name' => 'random-file.pdf',
            'description' => 'Some random document',
        ]);

        // Search for 'project' - should find the first file
        $response = $this->getJson(action([FileCRUDController::class, 'index'], ['q' => 'project']));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has('data', 1)->etc(),
        );

        // Search for 'budget' - should find the second file
        $response = $this->getJson(action([FileCRUDController::class, 'index'], ['q' => 'budget']));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has('data', 1)->etc(),
        );
    }

    public function testFilterByTags(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        // Create files with different tags
        File::factory()->createOne([
            'uploaded_by' => $user->id,
            'tags' => ['important', 'project', 'finance'],
        ]);

        File::factory()->createOne([
            'uploaded_by' => $user->id,
            'tags' => ['important', 'urgent'],
        ]);

        File::factory()->createOne([
            'uploaded_by' => $user->id,
            'tags' => ['archive', 'old'],
        ]);

        // Filter by 'important' tag - should find 2 files
        $response = $this->getJson(action([FileCRUDController::class, 'index'], ['tags' => 'important']));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has('data', 2)->etc(),
        );

        // Filter by 'project' tag - should find 1 file
        $response = $this->getJson(action([FileCRUDController::class, 'index'], ['tags' => 'project']));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has('data', 1)->etc(),
        );

        // Filter by multiple tags - should find files with any of the tags
        $response = $this->getJson(action([FileCRUDController::class, 'index'], ['tags' => 'urgent,archive']));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has('data', 2)->etc(),
        );
    }

    public function testFilterByMimeType(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        // Create files with different MIME types
        File::factory()->createOne([
            'uploaded_by' => $user->id,
            'mime_type' => 'application/pdf',
        ]);

        File::factory()->createOne([
            'uploaded_by' => $user->id,
            'mime_type' => 'application/pdf',
        ]);

        File::factory()->createOne([
            'uploaded_by' => $user->id,
            'mime_type' => 'image/jpeg',
        ]);

        File::factory()->createOne([
            'uploaded_by' => $user->id,
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);

        // Filter by PDF files - should find 2
        $response = $this->getJson(action([FileCRUDController::class, 'index'], ['file_type' => 'pdf']));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has('data', 2)->etc(),
        );

        // Filter by image type - should find 1
        $response = $this->getJson(action([FileCRUDController::class, 'index'], ['file_type' => 'image']));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has('data', 1)->etc(),
        );
    }

    public function testFilterByUploaderId(): void
    {
        $user1 = User::factory()->superAdmin()->createOne();
        $user2 = User::factory()->createOne();
        $this->actingAs($user1);

        // Create files uploaded by different users
        File::factory()->count(3)->create(['uploaded_by' => $user1->id]);
        File::factory()->count(2)->create(['uploaded_by' => $user2->id]);

        // Filter by user1 - should find 3 files
        $response = $this->getJson(action([FileCRUDController::class, 'index'], ['uploader_id' => $user1->id]));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has('data', 3)->etc(),
        );

        // Filter by user2 - should find 2 files
        $response = $this->getJson(action([FileCRUDController::class, 'index'], ['uploader_id' => $user2->id]));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has('data', 2)->etc(),
        );
    }

    public function testFilterByEntityType(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        // Create files linked to different entity types
        $file1 = File::factory()->createOne(['uploaded_by' => $user->id]);
        $file2 = File::factory()->createOne(['uploaded_by' => $user->id]);
        $file3 = File::factory()->createOne(['uploaded_by' => $user->id]);

        // Link files to users (entity_type = 'user')
        FileLink::create([
            'file_id' => $file1->id,
            'entity_type' => User::MORPH_CLASS,
            'entity_id' => $user->id,
        ]);

        FileLink::create([
            'file_id' => $file2->id,
            'entity_type' => User::MORPH_CLASS,
            'entity_id' => $user->id,
        ]);

        // file3 is not linked to any entity

        // Filter by entity_type 'user' - should find 2 files
        $response = $this->getJson(action([FileCRUDController::class, 'index'], ['entity_type' => User::MORPH_CLASS]));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has('data', 2)->etc(),
        );
    }

    public function testFilterByEntityId(): void
    {
        $user1 = User::factory()->superAdmin()->createOne();
        $user2 = User::factory()->createOne();
        $this->actingAs($user1);

        // Create files
        $file1 = File::factory()->createOne(['uploaded_by' => $user1->id]);
        $file2 = File::factory()->createOne(['uploaded_by' => $user1->id]);
        $file3 = File::factory()->createOne(['uploaded_by' => $user1->id]);

        // Link files to different entities
        FileLink::create([
            'file_id' => $file1->id,
            'entity_type' => User::MORPH_CLASS,
            'entity_id' => $user1->id,
        ]);

        FileLink::create([
            'file_id' => $file2->id,
            'entity_type' => User::MORPH_CLASS,
            'entity_id' => $user1->id,
        ]);

        FileLink::create([
            'file_id' => $file3->id,
            'entity_type' => User::MORPH_CLASS,
            'entity_id' => $user2->id,
        ]);

        // Filter by entity_id for user1 - should find 2 files
        $response = $this->getJson(action([FileCRUDController::class, 'index'], ['entity_id' => $user1->id]));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has('data', 2)->etc(),
        );

        // Filter by entity_id for user2 - should find 1 file
        $response = $this->getJson(action([FileCRUDController::class, 'index'], ['entity_id' => $user2->id]));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has('data', 1)->etc(),
        );
    }

    public function testFilterByDateRange(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        // Create files at different times with unique identifiers
        $oldFile = File::factory()->createOne([
            'uploaded_by' => $user->id,
            'original_name' => 'old-file-datetest.pdf',
        ]);
        $oldFile->created_at = now()->subDays(10);
        $oldFile->save();

        $recentFile1 = File::factory()->createOne([
            'uploaded_by' => $user->id,
            'original_name' => 'recent-file1-datetest.pdf',
        ]);
        $recentFile1->created_at = now()->subDays(3);
        $recentFile1->save();

        $recentFile2 = File::factory()->createOne([
            'uploaded_by' => $user->id,
            'original_name' => 'recent-file2-datetest.pdf',
        ]);
        $recentFile2->created_at = now()->subDays(1);
        $recentFile2->save();

        // Filter by from - files created in last 5 days, combine with uploader to ensure isolation
        $response = $this->getJson(action([FileCRUDController::class, 'index'], [
            'from' => now()->subDays(5)->format('Y-m-d'),
            'uploader_id' => $user->id,
        ]));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has('data', 2)->etc(),
        );

        // Filter by to - files created before 2 days ago
        $response = $this->getJson(action([FileCRUDController::class, 'index'], [
            'to' => now()->subDays(2)->format('Y-m-d'),
            'uploader_id' => $user->id,
        ]));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has('data', 2)->etc(),
        );

        // Filter by date range - files between 8 and 2 days ago (should only get file from 3 days ago)
        $response = $this->getJson(action([FileCRUDController::class, 'index'], [
            'from' => now()->subDays(8)->format('Y-m-d'),
            'to' => now()->subDays(2)->format('Y-m-d'),
            'uploader_id' => $user->id,
        ]));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has('data', 1)->etc(),
        );
    }

    public function testCombinedFilters(): void
    {
        $user1 = User::factory()->superAdmin()->createOne();
        $user2 = User::factory()->createOne();
        $this->actingAs($user1);

        // Create files with various attributes
        $file1 = File::factory()->createOne([
            'uploaded_by' => $user1->id,
            'original_name' => 'project-report-important.pdf',
            'description' => 'Important project report',
            'mime_type' => 'application/pdf',
            'tags' => ['important', 'project'],
        ]);

        $file2 = File::factory()->createOne([
            'uploaded_by' => $user1->id,
            'original_name' => 'project-data-analysis.xlsx',
            'description' => 'Project data analysis',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'tags' => ['project', 'data'],
        ]);

        $file3 = File::factory()->createOne([
            'uploaded_by' => $user2->id,
            'original_name' => 'other-document.pdf',
            'description' => 'Other document',
            'mime_type' => 'application/pdf',
            'tags' => ['other'],
        ]);

        // Combine multiple filters: user1's PDFs with 'project' tag
        $response = $this->getJson(action([FileCRUDController::class, 'index'], [
            'uploader_id' => $user1->id,
            'file_type' => 'pdf',
            'tags' => 'project',
        ]));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has('data', 1)->etc(),
        );

        // Combine search query with uploader filter (search for 'project' in name/description)
        $response = $this->getJson(action([FileCRUDController::class, 'index'], [
            'uploader_id' => $user1->id,
            'q' => 'project',
        ]));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has('data', 2)->etc(),
        );
    }

    public function testFileTransformerStructure(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        // Create a file with all fields populated
        $file = File::factory()->createOne([
            'uploaded_by' => $user->id,
            'original_name' => 'test-document.pdf',
            'description' => 'Test file description',
            'tags' => ['important', 'project', 'test'],
            'visibility' => 'private',
            'storage_disk' => 'local',
            'storage_path' => 'files/2025/01/test-document.pdf',
            'size_bytes' => 1048576,
            'mime_type' => 'application/pdf',
            'checksum' => 'a1b2c3d4e5f6',
        ]);

        // Create a file link to test entities include
        FileLink::create([
            'file_id' => $file->id,
            'entity_type' => User::MORPH_CLASS,
            'entity_id' => $user->id,
        ]);

        $response = $this->getJson(action([FileCRUDController::class, 'index']));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json
                ->has('data', 1)
                ->has(
                    'data.0',
                    static fn (AssertableJson $fileJson) => $fileJson
                    ->has('id')
                    ->where('id', $file->id)
                    ->has('original_name')
                    ->where('original_name', 'test-document.pdf')
                    ->has('description')
                    ->where('description', 'Test file description')
                    ->has('tags')
                    ->where('tags', ['important', 'project', 'test'])
                    ->has('visibility')
                    ->where('visibility', 'private')
                    ->has('storage_disk')
                    ->where('storage_disk', 'local')
                    ->has('storage_path')
                    ->where('storage_path', 'files/2025/01/test-document.pdf')
                    ->has('size_bytes')
                    ->where('size_bytes', 1048576)
                    ->has('mime_type')
                    ->where('mime_type', 'application/pdf')
                    ->has('checksum')
                    ->where('checksum', 'a1b2c3d4e5f6')
                    ->has('file_url')
                    ->whereType('file_url', 'string')
                    ->has('uploaded_by')
                    ->where('uploaded_by', $user->id)
                    ->etc()
                )
                ->etc()
        );
    }

    public function testFileTransformerWithIncludes(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        // Create a file
        $file = File::factory()->createOne([
            'uploaded_by' => $user->id,
            'original_name' => 'test-with-includes.pdf',
        ]);

        // Create file links to test entities include
        FileLink::create([
            'file_id' => $file->id,
            'entity_type' => User::MORPH_CLASS,
            'entity_id' => $user->id,
        ]);

        // Refresh the file to ensure relationship is available
        $file->refresh();

        // Test with entities include
        $response = $this->getJson(action([FileCRUDController::class, 'index'], ['include' => 'entities']));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json
                ->has('data', 1)
                ->has('data.0.entities.data', 1)
                ->has(
                    'data.0.entities.data.0',
                    static fn (AssertableJson $entityJson) => $entityJson
                    ->has('file_id')
                    ->where('file_id', $file->id)
                    ->has('entity_type')
                    ->where('entity_type', User::MORPH_CLASS)
                    ->has('entity_id')
                    ->where('entity_id', $user->id)
                    ->etc()
                )
                ->etc()
        );

        // Test with uploaded_by include
        $response = $this->getJson(action([FileCRUDController::class, 'index'], ['include' => 'uploaded_by']));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json
                ->has('data', 1)
                ->has(
                    'data.0.uploaded_by.data',
                    static fn (AssertableJson $userJson) => $userJson
                    ->has('id')
                    ->has('email')
                    ->etc()
                )
                ->etc()
        );

        // Test with both includes
        $response = $this->getJson(action([FileCRUDController::class, 'index'], ['include' => 'entities,uploaded_by']));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json
                ->has('data', 1)
                ->has('data.0.entities.data', 1)
                ->has('data.0.uploaded_by')
                ->etc()
        );
    }
}
