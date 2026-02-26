<?php

namespace App\Containers\AppSection\Authorization\Enums;

enum Role: string
{
    case SUPER_ADMIN = 'super-admin';
    case ADMIN = 'admin';
    case USER = 'user';

    public const ROLES_ARRAY = [
        self::SUPER_ADMIN,
        self::ADMIN,
        self::USER,
    ];

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::ADMIN => 'Admin',
            self::USER => 'User',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Admin user who has all the permissions along with controlling others permissions',
            self::ADMIN => 'Admin user who has access to all app data and functions',
            self::USER => 'Typical user who can view and edit his/her related projects or tasks',
        };
    }
}
