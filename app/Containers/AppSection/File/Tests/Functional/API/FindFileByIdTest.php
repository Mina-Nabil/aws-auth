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
final class FindFileByIdTest extends ApiTestCase
{
    public function testCanFindFileById(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $file = File::factory()->createOne(['uploaded_by' => $user->id]);

        $response = $this->getJson(action(
            [FileCRUDController::class, 'show'],
            ['file_id' => $file->id],
        ));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has(
                'data',
                static fn (AssertableJson $json) => $json->where('original_name', $file->original_name)
                ->etc(),
            )->etc(),
        );
    }

    public function testGivenUserHasNoAccessPreventsOperation(): void
    {
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        $file = File::factory()->createOne();

        $response = $this->getJson(action(
            [FileCRUDController::class, 'show'],
            ['file_id' => $file->id],
        ));

        $response->assertForbidden();
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

        $response = $this->getJson(action(
            [FileCRUDController::class, 'show'],
            ['file_id' => $file->id],
        ));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json
                ->has(
                    'data',
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
        $response = $this->getJson(
            action([FileCRUDController::class, 'show'], ['file_id' => $file->id]) . '?include=entities'
        );

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json
                ->has(
                    'data',
                    static fn (AssertableJson $fileJson) => $fileJson
                    ->has('entities.data', 1)
                    ->has(
                        'entities.data.0',
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
                )
                ->etc()
        );

        // Test with uploaded_by include
        $response = $this->getJson(
            action([FileCRUDController::class, 'show'], ['file_id' => $file->id]) . '?include=uploaded_by'
        );

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json
                ->has(
                    'data',
                    static fn (AssertableJson $fileJson) => $fileJson
                    ->has(
                        'uploaded_by.data',
                        static fn (AssertableJson $userJson) => $userJson
                        ->has('id')
                        ->has('email')
                        ->etc()
                    )
                    ->etc()
                )
                ->etc()
        );

        // Test with both includes
        $response = $this->getJson(
            action([FileCRUDController::class, 'show'], ['file_id' => $file->id]) . '?include=entities,uploaded_by'
        );

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json
                ->has(
                    'data',
                    static fn (AssertableJson $fileJson) => $fileJson
                    ->has('entities.data', 1)
                    ->has('uploaded_by')
                    ->etc()
                )
                ->etc()
        );
    }
}
