<?php

namespace App\Containers\AppSection\File\Tests\Unit\UI\API\Requests;

use App\Containers\AppSection\File\API\Requests\ListEntityFilesRequest;
use App\Containers\AppSection\File\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ListEntityFilesRequest::class)]
final class ListEntityFilesRequestTest extends UnitTestCase
{
    private ListEntityFilesRequest $request;

    public function testValidationRules(): void
    {
        $rules = $this->request->rules();

        $this->assertArrayHasKey('entity_type', $rules);
        $this->assertArrayHasKey('entity_id', $rules);
        $this->assertStringContainsString('required', $rules['entity_type']);
        $this->assertStringContainsString('in:', $rules['entity_type']);
        $this->assertStringContainsString('required', $rules['entity_id']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = new ListEntityFilesRequest();
    }
}
