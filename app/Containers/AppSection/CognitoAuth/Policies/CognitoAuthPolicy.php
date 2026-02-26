<?php

namespace App\Containers\AppSection\CognitoAuth\Policies;

use App\Containers\AppSection\User\Models\User;
use App\Ship\Parents\Policies\Policy as ParentPolicy;

final class CognitoAuthPolicy extends ParentPolicy
{
    public function confirmSignUp(User $user): bool
    {
        return $user->hasPermissionTo('confirm-cognito-sign-up');
    }
}
