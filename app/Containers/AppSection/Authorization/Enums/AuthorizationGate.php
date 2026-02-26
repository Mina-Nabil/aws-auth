<?php

namespace App\Containers\AppSection\Authorization\Enums;

enum AuthorizationGate: string
{
    case GIVE_PERMISSIONS = 'give-permissions';
    case VIEW_PERMISSIONS = 'view-permissions';
    case VIEW_ANY_PERMISSIONS = 'view-any-permissions';
    case CREATE_PERMISSIONS = 'create-permissions';
    case REVOKE_PERMISSIONS = 'revoke-permissions';
    case SYNC_PERMISSIONS = 'sync-permissions';
    case ASSIGN_ROLES = 'assign-roles';
    case CREATE_ROLES = 'create-roles';
    case DELETE_ROLES = 'delete-roles';
    case VIEW_ROLES = 'view-roles';
    case VIEW_ANY_ROLES = 'view-any-roles';
    case REVOKE_ROLES = 'revoke-roles';
    case SYNC_ROLES = 'sync-roles';
}
