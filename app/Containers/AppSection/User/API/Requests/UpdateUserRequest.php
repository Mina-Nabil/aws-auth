<?php

namespace App\Containers\AppSection\User\API\Requests;

use App\Ship\Parents\Requests\Request as ParentRequest;
use App\Containers\AppSection\Authorization\Enums\Role as RoleEnum;

final class UpdateUserRequest extends ParentRequest
{
    public function rules(): array
    {
        return [
            'first_name' => 'sometimes|min:2|max:50',
            'last_name' => 'sometimes|min:2|max:50',
            'email' => 'sometimes|email',
            "role" => 'sometimes|in:' . implode(',', array_map(fn ($role) => $role->value, RoleEnum::ROLES_ARRAY)),
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
