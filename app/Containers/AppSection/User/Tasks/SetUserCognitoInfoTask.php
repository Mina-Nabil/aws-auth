<?php 

namespace App\Containers\AppSection\User\Tasks;

use App\Containers\AppSection\User\Models\User;
use App\Containers\AppSection\User\Data\Repositories\UserRepository;
use App\Ship\Parents\Tasks\Task as ParentTask;
use App\Containers\AppSection\User\Models\CognitoUser;

final class SetUserCognitoInfoTask extends ParentTask
{

    public function __construct(
        private readonly UserRepository $repository,
    ) {
    }
    
    public function run(User|string $user, CognitoUser $cognitoUser): void
    {
        $userId = $user instanceof User ? $user->id : $user;
        $updates['cognito_id'] = $cognitoUser->cognito_id;
        $updates['is_cognito_user_confirmed'] = $cognitoUser->is_cognito_user_confirmed;
        $this->repository->update($updates, $userId);
        $user->refresh();
    }
}