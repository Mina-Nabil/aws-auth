<?php

namespace App\Containers\AppSection\File\Tests\Unit\UI\API\Requests;

use App\Containers\AppSection\File\API\Requests\UpdateFileRequest;
use App\Containers\AppSection\File\Enums\FileVisibility;
use App\Containers\AppSection\File\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UpdateFileRequest::class)]
final class UpdateFileRequestTest extends UnitTestCase
{
    private UpdateFileRequest $request;

    public function testValidationRules(): void
    {
        $rules = $this->request->rules();

        $this->assertArrayHasKey('description', $rules);
        $this->assertArrayHasKey('tags', $rules);
        $this->assertArrayHasKey('visibility', $rules);
        $this->assertStringContainsString('nullable', $rules['description']);
        $this->assertStringContainsString('required', $rules['visibility']);
        $this->assertStringContainsString('in:' . implode(',', array_map(fn (FileVisibility $visibility) => $visibility->value, FileVisibility::cases())), $rules['visibility']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = new UpdateFileRequest();
    }
}
