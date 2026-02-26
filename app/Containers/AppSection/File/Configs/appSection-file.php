<?php

use App\Containers\AppSection\User\Models\User;

return [
    'max_file_size' => env('MAX_FILE_SIZE', 10000), // default: 10MB
    'default_directory' => env('DEFAULT_DIRECTORY', 'files'),
    'temporary_url_duration' => env('TEMPORARY_URL_DURATION', 5),
    'tmp_directory' => env('TMP_DIRECTORY', 'tmp/'), // files to be deleted daily
    'allowed_entities' => [User::MORPH_CLASS],
];
