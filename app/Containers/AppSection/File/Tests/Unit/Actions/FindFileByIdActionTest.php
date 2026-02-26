<?php

namespace App\Containers\AppSection\File\Tests\Unit\Actions;

use App\Containers\AppSection\File\Actions\FindFileByIdAction;
use App\Containers\AppSection\File\Models\File;
use App\Containers\AppSection\File\Tests\UnitTestCase;
use App\Containers\AppSection\User\Models\User;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FindFileByIdAction::class)]
final class FindFileByIdActionTest extends UnitTestCase
{
    public function testCanFindFileById(): void
    {
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        $file = File::factory()->createOne(['uploaded_by' => $user->id]);

        $action = app(FindFileByIdAction::class);
        $result = $action->run($file->id);

        $this->assertInstanceOf(File::class, $result);
        $this->assertSame($file->id, $result->id);
        $this->assertSame($file->original_name, $result->original_name);
    }
}
