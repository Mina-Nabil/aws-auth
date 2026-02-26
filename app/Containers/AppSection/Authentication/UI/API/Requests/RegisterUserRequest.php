<?php

namespace App\Containers\AppSection\Authentication\UI\API\Requests;

use App\Ship\Parents\Requests\Request as ParentRequest;
use Illuminate\Validation\Rules\Password;

final class RegisterUserRequest extends ParentRequest
{
    protected array $decode = [];

    public function rules(): array
    {
        $this->merge(['email' => strtolower($this->input('email'))]);
        return [
            'first_name' => 'required|min:2|max:50',
            'last_name' => 'required|min:2|max:50',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', Password::default(), 'confirmed'],
        ];
    }

    public function authorize(): bool
    {
        return config('appSection-authentication.user_registration_enabled', false);
    }
}
