<?php

namespace App\Containers\AppSection\Authorization\API\Requests;

use App\Containers\AppSection\Authorization\Models\Permission;
use App\Ship\Parents\Requests\Request as ParentRequest;
use Illuminate\Support\Facades\Gate;
use App\Containers\AppSection\Authorization\Enums\AuthorizationGate;

final class SyncRolePermissionsRequest extends ParentRequest
{
    protected array $decode = [
        'role_id',
        'permission_ids.*',
    ];

    public function rules(): array
    {
        return [
            'role_id' => 'exists:roles,id',
            'permission_ids' => 'array|required',
            'permission_ids.*' => 'required|exists:permissions,id',
        ];
    }

    public function authorize(): bool
    {
        return Gate::allows(AuthorizationGate::SYNC_PERMISSIONS);
    }
}
