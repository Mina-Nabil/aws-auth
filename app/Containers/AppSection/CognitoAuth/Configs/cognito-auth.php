<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cognito Auth Active
    |--------------------------------------------------------------------------
    |
    | This option defines if the cognito auth is active.
    | If true, the cognito auth will be used for authentication.
    | If false, the cognito auth will not be used for authentication.
    |
    */
    'auth_active' => env('COGNITO_AUTH_ACTIVE', false),


    /*
    |--------------------------------------------------------------------------
    | Cognito Inapp Auth Endpoints Active
    |--------------------------------------------------------------------------
    |
    | This option defines if the cognito inapp auth endpoints are active.
    | If true, the application will include login, register, forgot password, etc. endpoints.
    | If false, the cognito AWS Cognito will handle the authentication directly from frontend apps.
    |
    */
    'include_auth_endpoints' => env('COGNITO_INCLUDE_AUTH_ENDPOINTS', false),

    /*
    |--------------------------------------------------------------------------
    | Cognito Sync User Pool Active
    |--------------------------------------------------------------------------
    |
    | This option defines if the cognito sync user pool is active.
    | If true, user changes done in this app will be synced with the user pool. UserServiceProvider will load the CognitoClient for Cognito API calls
    | If false, user changes done in this app will not be synced with the user pool.
    |
    */
    'sync_user_pool_active' => env('COGNITO_SYNC_USER_POOL_ACTIVE', false),

    /*
    |--------------------------------------------------------------------------
    | Cognito User Provider
    |--------------------------------------------------------------------------
    |
    | This option defines the user provider for the cognito user pool.
    | Available options: 'cognito_users', 'users'
    | If 'cognito_users', the user saved on this app will be a thin user model that only contains the cognito user id. (id column is the cognito user id)
    | If 'users', the user saved on this app will be a full user model that contains all the user data. (cognito_id column is the cognito user id)
    |
    */
    'user_provider' => env('COGNITO_USER_PROVIDER', 'cognito_users'),

    /*
    |--------------------------------------------------------------------------
    | Cognito Endpoint
    |--------------------------------------------------------------------------
    |
    | This option defines the endpoint of the cognito user pool, used for local development.
    | If empty, the AWS Cognito user pool will be used.
    |
    */
    'endpoint' => env('COGNITO_ENDPOINT', ''),

    /*
    |--------------------------------------------------------------------------
    | Cognito Region
    |--------------------------------------------------------------------------
    |
    | This option defines the region of the cognito user pool.
    |
    */
    'region' => env('COGNITO_REGION', 'us-east-1'),

    /*
    |--------------------------------------------------------------------------
    | Cognito Version
    |--------------------------------------------------------------------------
    |
    | This option defines the version of the cognito user pool.
    |
    */
    'version' => env('COGNITO_VERSION', '2016-04-18'),

    /*
    |--------------------------------------------------------------------------
    | Cognito Credentials
    |--------------------------------------------------------------------------
    |
    | Credentials for the Cognito Identity Provider client. When using a custom
    | endpoint (e.g. local Docker), set these or AWS_ACCESS_KEY_ID/AWS_SECRET_ACCESS_KEY
    | so the SDK does not fall back to the EC2 instance profile (169.254.169.254).
    |
    */
    'credentials' => [
        'key' => env('COGNITO_ACCESS_KEY_ID', env('AWS_ACCESS_KEY_ID')),
        'secret' => env('COGNITO_SECRET_ACCESS_KEY', env('AWS_SECRET_ACCESS_KEY')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cognito App Client ID
    |--------------------------------------------------------------------------
    |
    | This option defines the app client id of the cognito user pool.
    |
    */
    'app_client_id' => env('COGNITO_APP_CLIENT_ID', ''),

    /*
    |--------------------------------------------------------------------------
    | Cognito App Client Secret
    |--------------------------------------------------------------------------
    |
    | This option defines the app client secret of the cognito user pool.
    |
    */
    'app_client_secret' => env('COGNITO_APP_CLIENT_SECRET', ''),


    /*
    |--------------------------------------------------------------------------
    | Cognito User Pool ID
    |--------------------------------------------------------------------------
    |
    | This option defines the user pool id of the cognito user pool.
    |
    */
    'user_pool_id' => env('COGNITO_USER_POOL_ID', ''),

];
