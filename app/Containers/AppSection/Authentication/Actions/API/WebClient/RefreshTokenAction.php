<?php

namespace App\Containers\AppSection\Authentication\Actions\API\WebClient;

use App\Containers\AppSection\Authentication\Data\DTOs\PasswordToken;
use App\Containers\AppSection\Authentication\Data\Factories\PasswordTokenFactory;
use App\Containers\AppSection\Authentication\Values\Clients\WebClient;
use App\Containers\AppSection\Authentication\Values\RefreshToken;
use App\Containers\AppSection\Authentication\Values\RequestProxies\PasswordGrant\RefreshTokenProxy;
use App\Ship\Parents\Actions\Action as ParentAction;
use Illuminate\Support\Facades\Log;

final class RefreshTokenAction extends ParentAction
{
    public function __construct(
        private readonly PasswordTokenFactory $factory,
    ) {
    }

    public function run(RefreshToken $refreshToken): PasswordToken
    {
        Log::info('Refreshing token');
        return $this->factory->make(
            RefreshTokenProxy::create(
                $refreshToken,
                WebClient::create(),
            ),
        );
    }
}
