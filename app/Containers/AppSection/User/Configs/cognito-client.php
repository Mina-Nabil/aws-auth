<?php 

return [
    /*
    |--------------------------------------------------------------------------
    | Sync User Deletion
    |--------------------------------------------------------------------------
    |
    | This option defines if the user deletion should be synced with the user pool.
    | If true, the user will be deleted from the user pool if the user is deleted from this application's database.
    | If false, the user will NOT be deleted from the user pool
    */
    'sync_user_deletion' => env('COGNITO_SYNC_USER_DELETION', false),
];