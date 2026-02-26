<?php

namespace App\Containers\AppSection\File\Tests\Unit\UI\API\Requests;

use App\Containers\AppSection\File\API\Requests\ShowFileRequest;
use App\Containers\AppSection\File\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ShowFileRequest::class)]
final class ShowFileRequestTest extends UnitTestCase
{
    private ShowFileRequest $request;

    public function testValidationRules(): void
    {
        $rules = $this->request->rules();

        $this->assertSame([], $rules);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = new ShowFileRequest();
    }
}
