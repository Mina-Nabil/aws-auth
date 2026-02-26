<?php

namespace App\Containers\AppSection\UserTable\API\Requests;

use App\Containers\AppSection\UserTable\Enums\UserTableGate;
use App\Ship\Parents\Requests\Request as ParentRequest;
use Illuminate\Support\Facades\Gate;

final class DeleteUserTableRequest extends ParentRequest
{
    public function prepareForValidation(): void
    {
        $this->merge([
            'table_name' => $this->route('table_name'),
        ]);
    }

    public function rules(): array
    {
        return [
            'table_name' => 'required|string|max:255',
        ];
    }

    public function authorize(): bool
    {
        return Gate::allows(UserTableGate::DELETE_USER_TABLE);
    }
}
