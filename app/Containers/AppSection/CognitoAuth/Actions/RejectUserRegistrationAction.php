<?php 

namespace App\Containers\AppSection\CognitoAuth\Actions;

use App\Containers\AppSection\User\Models\User;
use App\Containers\AppSection\User\Tasks\ForceDeleteUserTask;
use App\Containers\AppSection\CognitoAuth\Enums\CognitoAuthGate;
use Illuminate\Support\Facades\Gate;
use App\Ship\Parents\Actions\Action as ParentAction;
use Illuminate\Support\Facades\DB;
use App\Containers\AppSection\User\Services\CognitoSyncClient;

final class RejectUserRegistrationAction extends ParentAction
{
    public function __construct(
        private readonly ForceDeleteUserTask $forceDeleteUserTask,
        private readonly CognitoSyncClient $cognitoSyncClient,
    ) {
    }

    public function run(User $user): void
    {
        Gate::authorize(CognitoAuthGate::CONFIRM_SIGN_UP);
        DB::transaction(function () use ($user) {
            $this->cognitoSyncClient->deleteUser($user->email);
            $this->forceDeleteUserTask->run($user);
        });
    }
}