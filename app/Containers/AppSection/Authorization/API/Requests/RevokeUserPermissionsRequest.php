<?php

namespace App\Containers\AppSection\Authorization\API\Requests;

use App\Containers\AppSection\Authorization\Models\Permission;
use App\Ship\Parents\Requests\Request as ParentRequest;
use Illuminate\Support\Facades\Gate;
use App\Containers\AppSection\Authorization\Enums\AuthorizationGate;

final class RevokeUserPermissionsRequest extends ParentRequest
{
    protected array $decode = [
        'user_id',
        'permission_ids.*',
    ];

    public function rules(): array
    {
        return [
            'user_id' => 'exists:users,id',
            'permission_ids' => 'array|required',
            'permission_ids.*' => 'exists:permissions,id',
        ];
    }

    public function authorize(): bool
    {
        return Gate::allows(AuthorizationGate::REVOKE_PERMISSIONS);
    }
}
