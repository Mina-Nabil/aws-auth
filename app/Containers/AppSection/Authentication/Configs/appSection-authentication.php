<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication Enabled
    |--------------------------------------------------------------------------
    |
    | This option defines if the authentication is enabled, if false the middleware 'auth:api' will not be applied to the routes.
    */
    'authentication_enabled' => env('AUTHENTICATION_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Clients
    |--------------------------------------------------------------------------
    |
    | A list of clients that have access to the application.
    |
    */
    'clients' => [
        'web' => [
            'id' => env('CLIENT_WEB_ID'),
            'secret' => env('CLIENT_WEB_SECRET'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Login With Custom Field
    |--------------------------------------------------------------------------
    |
    | This allows you to choose which field you want to use for passport auth.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Access Token Expiration Time
    |--------------------------------------------------------------------------
    |
    | In Minutes. Default to 1,440 minutes = 1 day
    |
    */
    'tokens-expire-in' => env('API_TOKEN_EXPIRES', 1440),

    /*
    |--------------------------------------------------------------------------
    | Refresh Token Expiration Time
    |--------------------------------------------------------------------------
    |
    | In Minutes. Default to 43,200 minutes = 30 days
    |
    */
    'refresh-tokens-expire-in' => env('API_REFRESH_TOKEN_EXPIRES', 43200),

    /*
    |--------------------------------------------------------------------------
    | Registration Enabled
    |--------------------------------------------------------------------------
    |
    | If true, users can register themselves.
    */
    'user_registration_enabled' => env('AUTH_REG_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | API URL
    |--------------------------------------------------------------------------
    |
    | The URL of the API.
    */
    'api-url' => env('API_URL', 'http://localhost:8000'),

    /*
    |--------------------------------------------------------------------------
    | API Public URL
    |--------------------------------------------------------------------------
    |
    | The URL of the API public.
    */
    'app-url' => env('APP_URL', 'http://localhost:8000'),

    /*
    |--------------------------------------------------------------------------
    | Github URL
    |--------------------------------------------------------------------------
    |
    | The URL of the Github.
    */
    'github-url' => env('GITHUB_REPO_URL', 'https://github.com/Safety-Shield/system-template'),


];
