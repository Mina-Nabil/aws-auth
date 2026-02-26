<?php

namespace App\Containers\AppSection\File\Tests\Unit\Actions;

use App\Containers\AppSection\File\Actions\ListFilesAction;
use App\Containers\AppSection\File\Enums\FileType;
use App\Containers\AppSection\File\Models\File;
use App\Containers\AppSection\File\Tests\UnitTestCase;
use App\Containers\AppSection\User\Models\User;
use PHPUnit\Framework\Attributes\CoversClass;
use Carbon\Carbon;

#[CoversClass(ListFilesAction::class)]
final class ListFilesActionTest extends UnitTestCase
{
    public function testCanListFiles(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        File::factory()->count(3)->create(['uploaded_by' => $user->id]);

        $action = app(ListFilesAction::class);
        $result = $action->run();

        $this->assertGreaterThanOrEqual(3, $result->total());
    }

    public function testListFilesWithFilterCombinations(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        // Create files with specific characteristics
        $file1 = File::factory()->create([
            'uploaded_by' => $user->id,
            'tags' => ['important', 'document'],
            'mime_type' => 'application/pdf',
            'created_at' => Carbon::now()->subDays(5),
        ]);

        $file2 = File::factory()->create([
            'uploaded_by' => $user->id,
            'tags' => ['important', 'image'],
            'mime_type' => 'image/jpeg',
            'created_at' => Carbon::now()->subDays(3),
        ]);

        $file3 = File::factory()->create([
            'uploaded_by' => $user->id,
            'tags' => ['document'],
            'mime_type' => 'application/pdf',
            'created_at' => Carbon::now()->subDays(1),
        ]);

        $action = app(ListFilesAction::class);

        // Test filter by tags
        $result = $action->run(tags: 'important');
        $this->assertGreaterThanOrEqual(2, $result->total());

        // Test filter by mime type
        $result = $action->run(fileType: FileType::PDF);
        $this->assertGreaterThanOrEqual(2, $result->total());

        // Test filter by date range
        $result = $action->run(
            after: Carbon::now()->subDays(4),
            before: Carbon::now()
        );
        $this->assertGreaterThanOrEqual(2, $result->total());

        // Test combining filters
        $result = $action->run(
            tags: 'important',
            fileType: FileType::PDF
        );
        $this->assertGreaterThanOrEqual(1, $result->total());
    }

    public function testListFilesWithPagination(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        // Create a large number of files
        File::factory()->count(25)->create(['uploaded_by' => $user->id]);

        // Mock request to set pagination limit
        request()->merge(['limit' => 10]);

        $action = app(ListFilesAction::class);
        $result = $action->run();

        $this->assertGreaterThanOrEqual(25, $result->total());
        $this->assertLessThanOrEqual(10, $result->count());
    }
}
