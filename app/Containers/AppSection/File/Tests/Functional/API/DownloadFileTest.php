<?php

namespace App\Containers\AppSection\File\Tests\Functional\API;

use App\Containers\AppSection\File\API\Controllers\FileCRUDController;
use App\Containers\AppSection\File\Enums\FileVisibility;
use App\Containers\AppSection\File\Models\File;
use App\Containers\AppSection\File\Tests\Functional\ApiTestCase;
use App\Containers\AppSection\User\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FileCRUDController::class)]
final class DownloadFileTest extends ApiTestCase
{
    public function testCanDownloadFile(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $file = File::factory()->createOne(['uploaded_by' => $user->id]);

        $response = $this->getJson(action(
            [FileCRUDController::class, 'download'],
            ['file_id' => $file->id],
        ));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has('url')->etc(),
        );
    }

    public function testGivenUserHasNoAccessPreventsOperation(): void
    {
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        $file = File::factory()->createOne();

        $response = $this->getJson(action(
            [FileCRUDController::class, 'download'],
            ['file_id' => $file->id],
        ));

        $response->assertForbidden();
    }

    public function testPrivateFileGeneratesTemporaryUrlWithConfiguredDuration(): void
    {
        Storage::fake('s3');

        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $testPath = 'files/test-private-file.pdf';
        Storage::put($testPath, 'test content');

        // Create a private file
        $file = File::factory()->createOne([
            'uploaded_by' => $user->id,
            'visibility' => FileVisibility::PRIVATE,
            'storage_disk' => 's3',
            'storage_path' => $testPath,
        ]);

        $response = $this->getJson(action(
            [FileCRUDController::class, 'download'],
            ['file_id' => $file->id],
        ));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has('url')->etc(),
        );

        // Verify that a temporary URL was generated (not a permanent public URL)
        $url = $response->json('url');
        $this->assertNotNull($url);
        $this->assertIsString($url);
    }

    public function testPublicFileDoesNotGenerateTemporaryUrl(): void
    {
        Storage::fake('s3');

        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $testPath = 'files/test-public-file.pdf';
        Storage::put($testPath, 'test content');

        // Create a public file
        $file = File::factory()->createOne([
            'uploaded_by' => $user->id,
            'visibility' => FileVisibility::PUBLIC,
            'storage_disk' => 's3',
            'storage_path' => $testPath,
        ]);

        $response = $this->getJson(action(
            [FileCRUDController::class, 'download'],
            ['file_id' => $file->id],
        ));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json) => $json->has('url')->etc(),
        );

        // Public files should get a permanent URL (no expiration parameters)
        $url = $response->json('url');
        $this->assertNotNull($url);
        $this->assertIsString($url);
    }

    public function testTemporaryUrlExpirationWithTimeTravel(): void
    {
        Storage::fake('s3');

        // Set temporary URL duration to 1 minute for faster testing
        Config::set('appSection-file.temporary_url_duration', 1);

        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $testPath = 'files/test-expiring-file.pdf';
        Storage::put($testPath, 'test content');

        $file = File::factory()->createOne([
            'uploaded_by' => $user->id,
            'visibility' => FileVisibility::PRIVATE,
            'storage_disk' => 's3',
            'storage_path' => $testPath,
        ]);

        // Generate the first temporary URL
        $firstUrl = $file->getSignedUrl();

        // Travel forward in time past the expiration (1 minute + buffer)
        $this->travel(2)->minutes();

        // Generate a new URL after time travel
        $secondUrl = $file->getSignedUrl();

        // The URLs should be different because they have different expiration times
        $this->assertNotEquals($firstUrl, $secondUrl);

        // Travel back to present
        $this->travelBack();
    }

    public function testDifferentConfigurationValuesGenerateDifferentUrls(): void
    {
        Storage::fake('s3');

        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $testPath = 'files/test-config-variations.pdf';
        Storage::put($testPath, 'test content');

        $file = File::factory()->createOne([
            'uploaded_by' => $user->id,
            'visibility' => FileVisibility::PRIVATE,
            'storage_disk' => 's3',
            'storage_path' => $testPath,
        ]);

        // Generate URL with 5 minute duration
        Config::set('appSection-file.temporary_url_duration', 5);
        $url5Minutes = $file->getSignedUrl();

        // Advance time by 1 second to ensure different timestamps
        $this->travel(1)->second();

        // Generate URL with 10 minute duration
        Config::set('appSection-file.temporary_url_duration', 10);
        $url10Minutes = $file->getSignedUrl();

        // URLs should be different due to different expiration times
        $this->assertNotEquals($url5Minutes, $url10Minutes);

        $this->travelBack();
    }

    public function testAccessingExpiredTemporaryUrlViaApi(): void
    {
        Storage::fake('s3');

        // Set a very short expiration for testing
        Config::set('appSection-file.temporary_url_duration', 1);

        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $testPath = 'files/test-api-expiration.pdf';
        Storage::put($testPath, 'test content');

        $file = File::factory()->createOne([
            'uploaded_by' => $user->id,
            'visibility' => FileVisibility::PRIVATE,
            'storage_disk' => 's3',
            'storage_path' => $testPath,
        ]);

        // Get initial URL
        $response = $this->getJson(action(
            [FileCRUDController::class, 'download'],
            ['file_id' => $file->id],
        ));

        $response->assertOk();
        $firstUrl = $response->json('url');

        // Travel past expiration time
        $this->travel(2)->minutes();

        // Request a new download URL (should generate a fresh one)
        $response = $this->getJson(action(
            [FileCRUDController::class, 'download'],
            ['file_id' => $file->id],
        ));

        $response->assertOk();
        $secondUrl = $response->json('url');

        // The new URL should be different from the expired one
        $this->assertNotEquals($firstUrl, $secondUrl);

        $this->travelBack();
    }
}
