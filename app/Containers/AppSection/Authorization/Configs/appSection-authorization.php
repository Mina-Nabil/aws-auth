<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Authorization Enabled
    |--------------------------------------------------------------------------
    |
    | This option defines if the authorization is enabled, if false all the policies checks will be skipped.
    */

    'authorization_enabled' => env('AUTHORIZATION_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | AppSection Section Authorization Container
    |--------------------------------------------------------------------------
    |
    |
    |
    */
    'first_admin_enabled' => env('FIRST_ADMIN_EMAIL') && env('FIRST_ADMIN_PW'),
    'first_admin_email' => env('FIRST_ADMIN_EMAIL'),
    'first_admin_pw' => env('FIRST_ADMIN_PW'),

    /*
    |--------------------------------------------------------------------------
    | Permissions Files
    |--------------------------------------------------------------------------
    |
    | The files to load permissions from.
    |
    */
    'permissions_files' => [
        'app/Containers/*/*/permissions.json',
    ],

    /*
    |--------------------------------------------------------------------------
    | Require Password Reset
    |--------------------------------------------------------------------------
    |
    | If true, users will be required to reset their password after login.
    |
    */
    'require_pw_reset' => env('REQUIRE_PW_RESET', false),
];
