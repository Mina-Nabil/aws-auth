<?php

namespace App\Containers\AppSection\User\API\Requests;

use App\Containers\AppSection\User\Models\User;
use App\Ship\Parents\Requests\Request as ParentRequest;
use Illuminate\Support\Facades\Gate;
use App\Containers\AppSection\User\Enums\UserGate;

final class ListUsersRequest extends ParentRequest
{
    public function rules(): array
    {
        return [];
    }

    public function authorize(): bool
    {
        return Gate::allows(UserGate::VIEW_ANY_USER);
    }
}
