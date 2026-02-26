<?php

namespace App\Containers\AppSection\Authorization\API\Requests;

use App\Containers\AppSection\Authorization\Models\Permission;
use App\Ship\Parents\Requests\Request as ParentRequest;
use Illuminate\Support\Facades\Gate;
use App\Containers\AppSection\Authorization\Enums\AuthorizationGate;

final class FindPermissionByIdRequest extends ParentRequest
{
    protected array $decode = [
        'permission_id',
    ];

    public function rules(): array
    {
        return [];
    }

    public function authorize(): bool
    {
        return Gate::allows(AuthorizationGate::VIEW_PERMISSIONS);
    }
}
