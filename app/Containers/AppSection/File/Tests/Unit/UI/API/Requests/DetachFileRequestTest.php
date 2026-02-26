<?php

namespace App\Containers\AppSection\File\Tests\Unit\UI\API\Requests;

use App\Containers\AppSection\File\API\Requests\DetachFileRequest;
use App\Containers\AppSection\File\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DetachFileRequest::class)]
final class DetachFileRequestTest extends UnitTestCase
{
    private DetachFileRequest $request;

    public function testValidationRules(): void
    {
        $rules = $this->request->rules();

        $this->assertArrayHasKey('file_id', $rules);
        $this->assertArrayHasKey('entity_type', $rules);
        $this->assertArrayHasKey('entity_id', $rules);
        $this->assertStringContainsString('required', $rules['file_id']);
        $this->assertStringContainsString('uuid', $rules['file_id']);
        $this->assertStringContainsString('exists:files,id', $rules['file_id']);
        $this->assertStringContainsString('required', $rules['entity_type']);
        $this->assertStringContainsString('in:', $rules['entity_type']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = new DetachFileRequest();
    }
}
