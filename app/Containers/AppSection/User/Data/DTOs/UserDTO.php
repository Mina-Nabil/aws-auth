<?php

namespace App\Containers\AppSection\User\Data\DTOs;

use App\Containers\AppSection\Authorization\Enums\Role as RoleEnum;
use App\Ship\Parents\DTOs\DTO;
use Webmozart\Assert\Assert;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final readonly class UserDTO extends DTO
{
    public function __construct(
        public ?string $userId = null,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $email = null,
        public ?string $password = null,
        public ?string $role = null,
        public ?bool $isDisabled = null,
        public ?bool $passwordResetRequired = null,
        public ?string $cognitoId = null,
    ) {
        if ($this->email) {
            Assert::email($this->email);
        }
        if ($this->role) {
            Assert::oneOf($this->role, array_map(fn ($role) => $role->value, RoleEnum::ROLES_ARRAY));
        }
    }

    /**
     * Creates a new UserDTO from request or data arrays.
     * @param array $data The data for the UserDTO
     * @return UserDTO The UserDTO
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['user_id'] ?? null,
            $data['first_name'] ?? null,
            $data['last_name'] ?? null,
            $data['email'] ?? null,
            $data['password'] ?? null,
            $data['role'] ?? null,
            $data['is_disabled'] ?? null,
            $data['password_reset_required'] ?? null,
            $data['cognito_id'] ?? null,
        );
    }

    /**
     * Returns the role enum from the role string.
     * @return RoleEnum The role for the action
     */
    public function role(): RoleEnum | null
    {
        return $this->role ? RoleEnum::from($this->role) : null;
    }

    /**
     * Used for create action, best practice to use this function to get the data for the create action. Runs Assertions on the data.
     * @return array The data for the create action
     */
    public function getCreateArray(): array
    {
        Assert::stringNotEmpty($this->firstName, 'First name is required to create a user');
        Assert::stringNotEmpty($this->lastName, 'Last name is required to create a user');
        Assert::stringNotEmpty($this->email, 'Email is required to create a user');
        Assert::stringNotEmpty($this->password, 'Password is required to create a user');

        $data = [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => Str::lower($this->email),
            'password' => Hash::make($this->password),
            'cognito_id' => $this->cognitoId,
        ];

        if ($this->passwordResetRequired !== null) {
            $data['password_reset_required'] = $this->passwordResetRequired;
        }

        return $data;
    }

    /**
     * Used for all update actions, best practice to use this function to get the data for the update action. Runs Assertions on the data.
     * @return array The data for the update action
     */
    public function getUpdateArray(): array
    {
        Assert::nullOrStringNotEmpty($this->firstName, 'Can\'t update first name with an empty value');
        Assert::nullOrStringNotEmpty($this->lastName, 'Can\'t update last name with an empty value');
        Assert::nullOrStringNotEmpty($this->password, 'Can\'t update password with an empty value');
        Assert::nullOrStringNotEmpty($this->email, 'Can\'t update email with an empty value');
        Assert::nullOrEmail($this->email, 'Can\'t update email with an invalid value');
        Assert::nullOrBoolean($this->isDisabled, 'Can\'t update status with an empty value');

        $retArray = [];

        if ($this->firstName) {
            $retArray['first_name'] = $this->firstName;
        }
        if ($this->lastName) {
            $retArray['last_name'] = $this->lastName;
        }
        if ($this->isDisabled !== null) {
            $retArray['is_disabled'] = $this->isDisabled;
        }
        if ($this->email) {
            $retArray['email'] = $this->email;
        }
        if ($this->password) {
            $retArray['password'] = $this->password;
        }

        if ($this->passwordResetRequired !== null) {
            $retArray['password_reset_required'] = $this->passwordResetRequired;
        }

        return $retArray;
    }

    public function withTemporaryPassword(string $password): self
    {
        return new self(
            userId: $this->userId,
            firstName: $this->firstName,
            lastName: $this->lastName,
            email: $this->email,
            password: $password,
            role: $this->role,
            isDisabled: $this->isDisabled,
            passwordResetRequired: true,
            cognitoId: $this->cognitoId,
        );
    }

    public function withCognitoId(string $cognitoId): self
    {
        return new self(
            userId: $this->userId,
            firstName: $this->firstName,
            lastName: $this->lastName,
            email: $this->email,
            password: $this->password,
            role: $this->role,
            isDisabled: $this->isDisabled,
            passwordResetRequired: $this->passwordResetRequired,
            cognitoId: $cognitoId,
        );
    }
}
