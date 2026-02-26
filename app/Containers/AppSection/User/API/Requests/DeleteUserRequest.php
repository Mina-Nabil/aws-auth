<?php

namespace App\Containers\AppSection\User\API\Requests;

use App\Ship\Parents\Requests\Request as ParentRequest;

final class DeleteUserRequest extends ParentRequest
{
    public function rules(): array
    {
        return [];
    }

    public function authorize(): bool
    {
        return true;
    }
}
