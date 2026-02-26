<?php

namespace App\Containers\AppSection\UserTable\Enums;

enum UserTableGate: string
{
    case LIST_USER_TABLES = 'list-usertables-gate';
    case CREATE_OR_UPDATE_USER_TABLE = 'create-or-update-usertable-gate';
    case DELETE_USER_TABLE = 'delete-usertable-gate';
}
