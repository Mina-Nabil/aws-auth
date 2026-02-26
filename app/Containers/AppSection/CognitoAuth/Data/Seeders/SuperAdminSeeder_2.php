<?php

namespace App\Containers\AppSection\CognitoAuth\Data\Seeders;

use App\Containers\AppSection\Authorization\Data\Seeders\SuperAdminSeeder_2 as ParentSuperAdminSeeder_2;
use App\Containers\AppSection\Authorization\Enums\Role as RoleEnum;
use App\Containers\AppSection\User\Exceptions\CognitoUserNotFoundException;
use App\Containers\AppSection\User\Services\CognitoSyncClient;

final class SuperAdminSeeder_2 extends ParentSuperAdminSeeder_2
{
    public function __construct(
        private readonly CognitoSyncClient $cognitoSyncClient,
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

        if (!count($userData) > 0) return;

        try {
            $user = $this->cognitoSyncClient->getUser($userData['email']);
        } catch (CognitoUserNotFoundException) {
            $this->cognitoSyncClient->createUser($userData['email'], $userData['password'], [
                'email' => $userData['email'],
                'first_name' => $userData['first_name'],
                'last_name' => $userData['last_name'],
            ]);
        }
    }
}
