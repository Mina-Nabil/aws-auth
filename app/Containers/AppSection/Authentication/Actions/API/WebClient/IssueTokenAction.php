<?php

namespace App\Containers\AppSection\Authentication\Actions\API\WebClient;

use App\Containers\AppSection\Authentication\Data\DTOs\PasswordToken;
use App\Containers\AppSection\Authentication\Data\Factories\PasswordTokenFactory;
use App\Containers\AppSection\Authentication\Exceptions\LoginFailed;
use App\Containers\AppSection\Authentication\Values\Clients\WebClient;
use App\Containers\AppSection\Authentication\Values\RequestProxies\PasswordGrant\AccessTokenProxy;
use App\Containers\AppSection\Authentication\Values\UserCredential;
use App\Ship\Parents\Actions\Action as ParentAction;
use League\OAuth2\Server\Exception\OAuthServerException;
use Illuminate\Support\Facades\Log;

final class IssueTokenAction extends ParentAction
{
    public function __construct(
        private readonly PasswordTokenFactory $factory,
    ) {
    }

    public function run(UserCredential $credential): PasswordToken
    {
        try {
            Log::info('Issuing token');
            Log::debug('Credentials: ' . $credential->username() . ' - ' . $credential->password());
            $token = $this->factory->make(
                AccessTokenProxy::create(
                    $credential,
                    WebClient::create(),
                ),
            );
            Log::info('Token issued');
            return $token;
        } catch (OAuthServerException $e) {
            Log::error('Error issuing token: ' . $e->getMessage());
            throw new LoginFailed($e->getCode(), $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error issuing token: ' . $e->getMessage());
            throw $e;
        }
    }
}
