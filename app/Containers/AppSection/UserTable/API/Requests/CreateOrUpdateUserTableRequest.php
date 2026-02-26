<?php

namespace App\Containers\AppSection\UserTable\API\Requests;

use App\Containers\AppSection\UserTable\Enums\UserTableGate;
use App\Ship\Parents\Requests\Request as ParentRequest;
use Illuminate\Support\Facades\Gate;

final class CreateOrUpdateUserTableRequest extends ParentRequest
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
            'visible_columns' => 'present|array',
            'visible_columns.*' => 'string',
        ];
    }

    public function authorize(): bool
    {
        return Gate::allows(UserTableGate::CREATE_OR_UPDATE_USER_TABLE);
    }
}
