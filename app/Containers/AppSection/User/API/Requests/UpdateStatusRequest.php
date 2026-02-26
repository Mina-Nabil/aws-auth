<?php

namespace App\Containers\AppSection\User\API\Requests;

use App\Ship\Parents\Requests\Request as ParentRequest;

final class UpdateStatusRequest extends ParentRequest
{
    public function rules(): array
    {
        return [
           'is_disabled' => 'required|boolean',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
