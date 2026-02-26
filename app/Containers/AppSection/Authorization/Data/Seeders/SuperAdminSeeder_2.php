<?php

namespace App\Containers\AppSection\Authorization\Data\Seeders;

use App\Containers\AppSection\Authorization\Enums\Role as RoleEnum;
use App\Containers\AppSection\User\Actions\CreateUserAction;
use App\Containers\AppSection\User\Data\DTOs\UserDTO;
use App\Containers\AppSection\User\Models\User;
use App\Ship\Parents\Seeders\Seeder as ParentSeeder;

class SuperAdminSeeder_2 extends ParentSeeder
{

    public function __construct(
        private readonly CreateUserAction $createUserAction,
    ) {}

    public function run(): void
    {
        $userData = config('appSection-authorization.first_admin_enabled') ? [
            'email' => config('appSection-authorization.first_admin_email'),
            'password' => config('appSection-authorization.first_admin_pw'),
            'first_name' => "Super",
            'last_name' => "Admin",
            'role' => RoleEnum::SUPER_ADMIN->value,
        ] : [];
        if (count($userData) > 0 && !User::where('email', $userData['email'])->exists()) {
            $userDto = UserDTO::fromArray($userData);
            $this->createUserAction
                ->bypassGate()
                ->skipWelcomeEmail()
                ->skipTemporaryPassword()
                ->run($userDto, RoleEnum::SUPER_ADMIN);
        }
    }
}
