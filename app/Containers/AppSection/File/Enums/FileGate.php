<?php

namespace App\Containers\AppSection\File\Enums;

enum FileGate: string
{
    case VIEW_ANY_FILE = 'view-any-file-gate';
    case DOWNLOAD_ANY_FILE = 'download-any-file-gate';
    case VIEW_FILE = 'show-file-gate';
    case CREATE_FILE = 'create-file-gate';
    case UPDATE_FILE = 'update-file-gate';
    case DELETE_FILE = 'delete-file-gate';
}
