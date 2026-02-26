<?php

namespace App\Containers\AppSection\File\Tests\Unit\UI\API\Requests;

use App\Containers\AppSection\File\API\Requests\ListFilesRequest;
use App\Containers\AppSection\File\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ListFilesRequest::class)]
final class ListFilesRequestTest extends UnitTestCase
{
    private ListFilesRequest $request;

    public function testValidationRules(): void
    {
        $rules = $this->request->rules();

        $this->assertArrayHasKey('q', $rules);
        $this->assertArrayHasKey('tags', $rules);
        $this->assertArrayHasKey('file_type', $rules);
        $this->assertArrayHasKey('uploader_id', $rules);
        $this->assertArrayHasKey('entity_type', $rules);
        $this->assertArrayHasKey('entity_id', $rules);
        $this->assertArrayHasKey('from', $rules);
        $this->assertArrayHasKey('to', $rules);
        $this->assertStringContainsString('sometimes', $rules['q']);
        $this->assertStringContainsString('date', $rules['from']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = new ListFilesRequest();
    }
}
