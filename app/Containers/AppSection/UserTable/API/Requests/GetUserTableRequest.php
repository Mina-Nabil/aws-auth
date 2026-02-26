<?php

namespace App\Containers\AppSection\UserTable\API\Requests;

use App\Containers\AppSection\UserTable\Enums\UserTableGate;
use App\Ship\Parents\Requests\Request as ParentRequest;
use Illuminate\Support\Facades\Gate;

final class GetUserTableRequest extends ParentRequest
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
        return Gate::allows(UserTableGate::LIST_USER_TABLES);
    }
}
