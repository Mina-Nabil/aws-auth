<?php

namespace App\Containers\AppSection\User\Tests\Unit\Models;

use App\Containers\AppSection\User\Models\User;
use App\Containers\AppSection\User\Tests\UnitTestCase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(User::class)]
final class UserTest extends UnitTestCase
{
    public function testUsesCorrectTable(): void
    {
        $user = User::factory()->createOne();
        $table = 'users';

        $this->assertSame($table, $user->getTable());
    }

    public function testHasCorrectFillableFields(): void
    {
        $user = User::factory()->createOne();
        $fillable = [
            'first_name',
            'last_name',
            'email',
            'email_verified_at',
            'password',
            'is_disabled',
            'password_reset_required',
        ];

        $this->assertSame($fillable, $user->getFillable());
    }

    public function testHasCorrectCasts(): void
    {
        $user = User::factory()->createOne();
        $casts = [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_disabled' => 'boolean',
            'deleted_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'password_reset_required' => 'boolean',
        ];

        $this->assertSame($casts, $user->getCasts());
    }

    public function testHasCorrectHiddenFields(): void
    {
        $user = User::factory()->createOne();
        $hiddens = [
            'password',
            'remember_token',
            'deleted_at',
        ];

        $this->assertSame($hiddens, $user->getHidden());
    }

    public function testHasCorrectResourceKey(): void
    {
        $user = User::factory()->createOne();

        $this->assertSame('User', $user->getResourceKey());
    }

    public function testCanAuthenticateUsingEmail(): void
    {
        $user = User::factory()->createOne();

        $result = (new User())->findForPassport($user->email);

        $this->assertTrue($user->is($result));
    }

    public function testLowerCasesEmailOnAccess(): void
    {
        $original = 'GanDalf@thE.Gray';
        $expectedGet = 'gandalf@the.gray';
        $expectedSet = 'GanDalf@thE.Gray';
        $user = User::factory()->createOne(['email' => $original]);

        $this->assertSame($expectedGet, $user->email);
        $this->assertSame($expectedSet, DB::query()->from('users')->find($user->id)->email);
    }

    public function testUserFullNameAttribute(): void
    {
        $user = User::factory()->createOne();
        $this->assertSame($user->first_name . ' ' . $user->last_name, $user->full_name);
    }

    public function testUserRole(): void
    {
        $user = User::factory()->createOne();

        $this->assertFalse($user->is_super_admin);

        $user = User::factory()->superAdmin()->createOne();

        $this->assertTrue($user->is_super_admin);

        $user = User::factory()->admin()->createOne();

        $this->assertTrue($user->is_admin);

        $user = User::factory()->admin()->createOne();

        $this->assertTrue($user->is_admin);

        $user = User::factory()->user()->createOne();

        $this->assertTrue($user->is_user);
    }

    public function testIsModelSoftDeletable(): void
    {
        $user = User::factory()->createOne();
        $this->assertNull($user->deleted_at);

        $this->isSoftDeletableModel($user);
    }
}
