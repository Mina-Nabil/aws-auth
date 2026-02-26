<?php

namespace App\Containers\AppSection\CognitoAuth\Models;

use Apiato\Core\Models\InteractsWithApiato;
use Apiato\Http\Resources\ResourceKeyAware;

final class LoginResponse implements ResourceKeyAware
{
    use InteractsWithApiato;

    public function __construct(
        public string $accessToken,
        public string $idToken,
        public string $refreshToken,
        public int $expiresIn
    ) {}
}
