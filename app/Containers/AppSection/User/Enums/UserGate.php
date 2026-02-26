<?php

namespace App\Containers\AppSection\User\Enums;

enum UserGate: string
{
    case VIEW_ANY_USER = 'view-any-user-gate';
    case VIEW_USER = 'show-user-gate';
    case CREATE_USER = 'create-user-gate';
    case UPDATE_USER = 'update-user-gate';
    case UPDATE_USER_PASSWORD = 'update-user-password-gate';
    case SEND_TEMPORARY_PASSWORD = 'send-temporary-password-gate';
    case UPDATE_USER_STATUS = 'update-user-status-gate';
    case DELETE_USER = 'delete-user-gate';
    case LIST_USER_FILES = 'list-user-files-gate';
    case ATTACH_ANY_USER_FILES = 'attach-any-user-files-gate';
    case ATTACH_USER_FILES = 'attach-user-files-gate';
    case DETACH_USER_FILES = 'detach-user-files-gate';
    case DETACH_ANY_USER_FILES = 'detach-any-user-files-gate';
}
