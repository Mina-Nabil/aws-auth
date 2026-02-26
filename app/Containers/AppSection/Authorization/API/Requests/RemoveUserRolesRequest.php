<?php

namespace App\Containers\AppSection\Authorization\API\Requests;

use App\Containers\AppSection\Authorization\Models\Role;
use App\Ship\Parents\Requests\Request as ParentRequest;
use Illuminate\Support\Facades\Gate;
use App\Containers\AppSection\Authorization\Enums\AuthorizationGate;

final class RemoveUserRolesRequest extends ParentRequest
{
    protected array $decode = [
        'user_id',
        'role_ids.*',
    ];

    public function rules(): array
    {
        return [
            'user_id' => 'exists:users,id',
            'role_ids' => 'array|required',
            'role_ids.*' => 'required|exists:roles,id',
        ];
    }

    public function authorize(): bool
    {
        return Gate::allows(AuthorizationGate::REVOKE_ROLES);
    }
}
