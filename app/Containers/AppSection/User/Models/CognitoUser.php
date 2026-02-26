<?php

namespace App\Containers\AppSection\User\Models;

final class CognitoUser
{
    public function __construct(
        public string $cognito_id,
        public bool $is_cognito_user_confirmed,
    ) {}
}
