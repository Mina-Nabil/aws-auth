<?php

namespace App\Containers\AppSection\User\API\Requests;

use App\Ship\Parents\Requests\Request as ParentRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class UpdatePasswordRequest extends ParentRequest
{
    public function rules(): array
    {
        return [
            'current_password' => [
                Rule::requiredIf(fn (): bool => !is_null($this->user()->password)),
                'current_password:api',
            ],
            'new_password' => [
                'required',
                Password::default(),
            ],
            'new_password_confirmation' => 'required_with:new_password|same:new_password',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
