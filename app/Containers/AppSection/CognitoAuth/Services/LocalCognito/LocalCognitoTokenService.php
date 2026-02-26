<?php

namespace App\Containers\AppSection\CognitoAuth\Services\LocalCognito;

use App\Containers\AppSection\CognitoAuth\Services\AWS\CognitoTokenService;
use App\Containers\AppSection\CognitoAuth\Exceptions\InvalidTokenException;
use Ramsey\Uuid\Uuid;
use Throwable;


/**
 * A class for handling Cognito token operations.
 * This class is used to get the token from the request, decode the token, and get the Cognito UUID from the token.
 * It is used in the CognitoGuard class to get the user from the token.
 * 
 */
final class LocalCognitoTokenService extends CognitoTokenService
{

    /**
     * Although we already know the token has a valid signature and is
     * unexpired, this method is used to validate the payload.
     *
     * @param object $payload
     * @param $region
     * @param $poolId
     * @return void
     * @throws InvalidTokenException | Throwable
     */
    public function validatePayload(object $payload, $region, $poolId): void
    {
        $iss = $payload->iss ?? null;
        if ($iss === null || $iss === '') {
            throw new InvalidTokenException('Missing issuer (iss) claim');
        }

        $endpoint = config('cognito-auth.endpoint');
        $base = rtrim($endpoint, '/');
        $path = '/' . $poolId;
        $allowedIssuers[] = $base . $path;
        $allowedIssuers[] = $base;
        // cognito-local may use 0.0.0.0 or localhost instead of the configured host
        $parsed = parse_url($base);
        if (is_array($parsed)) {
            $scheme = ($parsed['scheme'] ?? 'http') . '://';
            $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
            foreach (['0.0.0.0', '127.0.0.1', 'localhost', $parsed['host'] ?? ''] as $host) {
                if ($host === '') {
                    continue;
                }
                $allowedIssuers[] = $scheme . $host . $port . $path;
                $allowedIssuers[] = $scheme . $host . $port;
            }
        }

        if (! in_array($iss, $allowedIssuers, true)) {
            throw new InvalidTokenException('Invalid issuer. Expected one of: ' . implode(', ', $allowedIssuers));
        }

        if (! in_array($payload->token_use, ['id', 'access'])) {
            throw new InvalidTokenException('Invalid token_use. Must be one of ["id","access"].');
        }

        $uuid = $payload->username ?? $payload->{'cognito:username'} ?? $payload->sub ?? null;
        if (! $uuid) {
            throw new InvalidTokenException('Invalid token attributes. Token must include one of "username", "cognito:username", "sub".');
        }

        if (! Uuid::isValid($uuid)) {
            throw new InvalidTokenException('Invalid token attributes. Subject (username/cognito:username/sub) must be a UUID.');
        }
    }
}
