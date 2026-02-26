<?php

namespace App\Containers\AppSection\Authorization\Tests\Unit\Tasks;

use App\Containers\AppSection\Authorization\Tasks\CreatePermissionTask;
use App\Containers\AppSection\Authorization\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CreatePermissionTask::class)]
final class CreatePermissionTaskTest extends UnitTestCase
{
    public function testCreatePermission(): void
    {
        $id = '1234567890';
        $guardName = 'api';
        $name = 'fuLl_coNtroL';
        $description = 'Gives full control of everything!';
        $displayName = 'Controller of All';
        $group = 'Full Control';
        $isActive = true;
        $version = '1.0.0';

        $permission = app(CreatePermissionTask::class)->run($id, $name, $description, $displayName, $guardName, $group, $isActive, $version);

        $this->assertSame(strtolower($name), $permission->name);
        $this->assertSame($description, $permission->description);
        $this->assertSame($displayName, $permission->display_name);
        $this->assertSame($group, $permission->group);
        $this->assertSame($isActive, $permission->is_active);
        $this->assertSame($version, $permission->version);
        $this->assertSame('api', $permission->guard_name);
    }
}
