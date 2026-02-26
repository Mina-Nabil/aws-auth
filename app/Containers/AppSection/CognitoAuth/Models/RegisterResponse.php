<?php

namespace App\Containers\AppSection\CognitoAuth\Models;

use Apiato\Core\Models\InteractsWithApiato;
use Apiato\Http\Resources\ResourceKeyAware;

final class RegisterResponse implements ResourceKeyAware
{
    use InteractsWithApiato;

    public function __construct(
        public string $message,
        public bool $userConfirmed
    ) {}
}
