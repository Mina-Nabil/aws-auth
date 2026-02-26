<?php

namespace App\Containers\AppSection\CognitoAuth\Services\LocalCognito;

use App\Containers\AppSection\CognitoAuth\Services\AWS\CognitoJWKsService;
use Illuminate\Support\Facades\Http;

/**
 * A class for downloading and caching the Cognito JWKS for the given user pool and region.
 * Should be used for local Cognito user pools.
 * 
 */
final class LocalCognitoJWKsService extends CognitoJWKsService
{

    /**
     * Download the jwks for the configured user pool
     *
     * @param string $region
     * @param string $poolId
     * @return string
     */
    public function downloadJwks(string $region, string $poolId): string
    {
        $endpoint = config('cognito-auth.endpoint');
        $url = rtrim($endpoint, '/') . '/' . $poolId . '/.well-known/jwks.json';

        $response = Http::timeout(10)->get($url);
        $response->throw();

        return $response->body();
    }
}
