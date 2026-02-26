<?php

namespace App\Containers\AppSection\User\Tests\Unit\Actions;

use App\Containers\AppSection\User\Actions\DeleteUserAction;
use App\Containers\AppSection\User\Models\User;
use App\Containers\AppSection\User\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Illuminate\Auth\Access\AuthorizationException;

#[CoversClass(DeleteUserAction::class)]
final class DeleteUserActionTest extends UnitTestCase
{
    public function testCanDeleteAnotherUser(): void
    {
        $this->expectException(AuthorizationException::class);

        /**
         * @var User $user
         */
        $user = User::factory()->user()->createOne();
        $this->actingAs($user, 'api');
        $anotherUser = User::factory()->user()->createOne();

        $action = app(DeleteUserAction::class);

        $result = $action->run($anotherUser->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted($anotherUser);
    }

    public function testCannotDeleteItself(): void
    {
        $this->expectException(AuthorizationException::class);

        /**
         * @var User $user
         */
        $user = User::factory()->createOne();
        $this->actingAs($user, 'api');
        $action = app(DeleteUserAction::class);

        $action->run($user->id);
    }
}
