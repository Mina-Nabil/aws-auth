<?php

namespace App\Containers\AppSection\CognitoAuth\Services\AWS;

use App\Containers\AppSection\CognitoAuth\Exceptions\InvalidTokenException;
use DomainException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Throwable;
use UnexpectedValueException;


/**
 * A class for handling Cognito token operations.
 * This class is used to get the token from the request, decode the token, and get the Cognito UUID from the token.
 * It is used in the CognitoGuard class to get the user from the token.
 * 
 */
class CognitoTokenService
{
    /**
     * Get the token from the request.
     *
     * @param Request $request
     * @return string|null
     */
    public function getTokenFromRequest(Request $request): ?string
    {
        $jwt = $request->bearerToken();

        if(! $jwt){
            // from cookie
            $prefix = 'CognitoIdentityServiceProvider_' . config('cognito-auth.app_client_id');
            $sub = $request->cookie($prefix . '_LastAuthUser');
            $jwt = $request->cookie($prefix . '_' . $sub . '_accessToken');
        }

        return $jwt;
    }

    /**
     * @param string $jwt
     * @return string
     * @throws InvalidTokenException|Throwable
     */
    public function getCognitoUuidFromToken(string $jwt): string
    {
        $payload = $this->decode($jwt);

        $cognitoUuid = $payload->username ?? $payload->{'cognito:username'} ?? $payload->sub ?? null;
        throw_unless($cognitoUuid, new InvalidTokenException('CognitoUuid not found'));

        return (string) $cognitoUuid;
    }

    /**
     * Get the email from the token payload (e.g. for user lookup when sub does not match Laravel user id).
     *
     * @param string $jwt
     * @return string|null
     */
    public function getEmailFromToken(string $jwt): ?string
    {
        try {
            $payload = $this->decode($jwt);
            return $payload->email ?? null;
        } catch (InvalidTokenException $e) {
            return null;
        }
    }

    /**
     * @param string $jwt
     * @return object
     * @throws InvalidTokenException
     */
    public function decode(string $jwt): object
    {
        $this->validateHeader($jwt);

        $region = config('cognito-auth.region');
        $poolId = config('cognito-auth.user_pool_id');
        $js     = app()->make(CognitoJWKsService::class);
        $keys   = $js->getJwks($region, $poolId);

        // When JWKS has exactly one key, pass that key directly so the library skips kid lookup
        // (cognito-local and some emulators can produce a key set where kid lookup fails).
        $keyOrKeySet = count($keys) === 1
            ? array_values($keys)[0]
            : $keys;

        try {
            // Library expects Key or array of kid => Key (not algorithm => key).
            $payload = JWT::decode($jwt, $keyOrKeySet);
        } catch (
            InvalidArgumentException
            | UnexpectedValueException
            | SignatureInvalidException
            | BeforeValidException
            | ExpiredException
            | DomainException
            $e
        ) {
            throw new InvalidTokenException($e->getMessage());
        }

        $this->validatePayload($payload, $region, $poolId);

        return $payload;
    }

    /**
     * Validates the header exists, can be base64 decoded, has a kid,
     * and has RS256 as alg
     *
     * @param string $jwt
     * @throws InvalidTokenException
     */
    public function validateHeader(string $jwt): void
    {
        $tks = explode('.', $jwt);
        if (count($tks) != 3) {
            throw new InvalidTokenException('Wrong number of segments');
        }

        try{
            $header = JWT::jsonDecode(JWT::urlsafeB64Decode($tks[0]));
        }catch(DomainException $e
        ){
            throw new InvalidTokenException($e->getMessage());
        }

        if(empty($header->kid)){
            throw new InvalidTokenException('No kid present in token header');
        }

        if(empty($header->alg)){
            throw new InvalidTokenException('No alg present in token header');
        }

        if($header->alg !== 'RS256'){
            throw new InvalidTokenException('The token alg is not RS256');
        }
    }

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

        $awsIssuer = sprintf('https://cognito-idp.%s.amazonaws.com/%s', $region, $poolId);
        $endpoint = config('cognito-auth.endpoint');
        $allowedIssuers = [$awsIssuer];
        if (! empty($endpoint)) {
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

    /**
     * Get the expiration time from the token.
     *
     * @param string $jwt
     * @return int
     * @throws InvalidTokenException
     */
    public function getExpirationTimeFromToken(string $jwt): int
    {
        $payload = $this->decode($jwt);
        return $payload->exp;
    }
}