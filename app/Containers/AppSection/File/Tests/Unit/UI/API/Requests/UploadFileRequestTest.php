<?php

namespace App\Containers\AppSection\File\Tests\Unit\UI\API\Requests;

use App\Containers\AppSection\File\API\Requests\UploadFileRequest;
use App\Containers\AppSection\File\Enums\FileVisibility;
use App\Containers\AppSection\File\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UploadFileRequest::class)]
final class UploadFileRequestTest extends UnitTestCase
{
    private UploadFileRequest $request;

    public function testValidationRules(): void
    {
        $rules = $this->request->rules();

        $this->assertArrayHasKey('file', $rules);
        $this->assertArrayHasKey('description', $rules);
        $this->assertArrayHasKey('tags', $rules);
        $this->assertArrayHasKey('visibility', $rules);
        $this->assertArrayHasKey('uploaded_by', $rules);
        $this->assertStringContainsString('required', $rules['file']);
        $this->assertStringContainsString('file', $rules['file']);
        $this->assertStringContainsString('in:' . implode(',', array_map(fn (FileVisibility $visibility) => $visibility->value, FileVisibility::cases())), $rules['visibility']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = new UploadFileRequest();
    }
}
