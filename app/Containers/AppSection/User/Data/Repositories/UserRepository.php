<?php

namespace App\Containers\AppSection\User\Data\Repositories;

use App\Containers\AppSection\User\Interfaces\UserDBServiceInterface;
use App\Containers\AppSection\User\Models\User;
use App\Ship\Parents\Repositories\Repository as ParentRepository;
use Illuminate\Support\Collection;

/**
 * @template TModel of User
 *
 * @extends ParentRepository<TModel>
 */
final class UserRepository extends ParentRepository implements UserDBServiceInterface
{
    protected $fieldSearchable = [
        'id' => '=',
        'first_name' => 'like',
        'last_name' => 'like',
        'email' => '=',
    ];

    public function loadQuery(
        ?array $ids = null,
        $role  = null,
        ?bool $isDisabled = null,
        ?bool $isCognitoUser = null,
        ?bool $isCognitoUserConfirmed = null,
    ): self {
        return $this->scopeQuery(function ($query) use ($ids, $role, $isDisabled, $isCognitoUser, $isCognitoUserConfirmed) {
            if ($ids !== null) {
                $query = $query->whereIn('id', $ids);
            }
            if ($role !== null) {
                $query = $query->role($role);
            }
            if ($isDisabled !== null) {
                $query = $query->isDisabled($isDisabled);
            }
            if ($isCognitoUser !== null) {
                $query = $query->isCognitoUser($isCognitoUser);
            }
            if ($isCognitoUserConfirmed !== null) {
                $query = $query->isCognitoUserConfirmed($isCognitoUserConfirmed);
            }
            return $query;
        });
    }

    public function getUserByEmail($email): User
    {
        return $this->where('email', $email)->first();
    }
    public function disableUser(User $user): bool
    {
        return $user->update(['is_disabled' => true]);
    }
    public function enableUser(User $user): bool
    {
        return $user->update(['is_disabled' => false]);
    }

    public function model(): string
    {
        return User::class;
    }
}
