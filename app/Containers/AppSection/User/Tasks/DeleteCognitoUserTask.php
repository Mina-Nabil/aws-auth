<?php

namespace App\Containers\AppSection\User\Tasks;

use App\Containers\AppSection\User\Models\User;
use App\Containers\AppSection\User\Services\CognitoSyncClient;
use App\Ship\Parents\Tasks\Task as ParentTask;

final class DeleteCognitoUserTask extends ParentTask
{
    public function run(User $user): void
    {
        $cognitoClient = app(CognitoSyncClient::class);
        $cognitoClient->deleteUser($user->email);
    }
}