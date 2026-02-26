<?php

namespace App\Containers\AppSection\User\API\Requests;

use App\Ship\Parents\Requests\Request as ParentRequest;
use Illuminate\Support\Facades\Gate;
use App\Containers\AppSection\User\Enums\UserGate;
use App\Containers\AppSection\Authorization\Enums\Role as RoleEnum;
use Illuminate\Validation\Rules\Password;

final class CreateUserRequest extends ParentRequest
{
    public function rules(): array
    {
        $rules = [
            'first_name' => 'required|min:2|max:50',
            'last_name' => 'required|min:2|max:50',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|in:' . implode(',', array_map(fn ($role) => $role->value, RoleEnum::ROLES_ARRAY)),
        ];

        if (!config('appSection-authorization.require_pw_reset')) {
            $rules['password'] = ['required', Password::default(), 'confirmed'];
        }

        return $rules;
    }

    public function authorize(): bool
    {
        return Gate::allows(UserGate::CREATE_USER);
    }
}
