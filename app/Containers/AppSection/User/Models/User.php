<?php

namespace App\Containers\AppSection\User\Models;

use App\Containers\AppSection\Authorization\Enums\Role as RoleEnum;
use App\Containers\AppSection\User\Data\Collections\UserCollection;
use App\Ship\Parents\Models\UserModel as ParentUserModel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\Notifiable;

final class User extends ParentUserModel implements MustVerifyEmail
{
    use HasUuids;
    use SoftDeletes;
    use Notifiable;

    public const MORPH_CLASS = 'user';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'email_verified_at',
        'password',
        'is_disabled',
        'password_reset_required',
        'cognito_id',
        'is_cognito_user_confirmed',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'deleted_at',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_disabled' => 'boolean',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'password_reset_required' => 'boolean',
        'is_cognito_user_confirmed' => 'boolean',
    ];

    /**
     * Create a new collection instance.
     */
    public function newCollection(array $models = []): UserCollection
    {
        return new UserCollection($models);
    }

    /**
     * Allows Passport to find the user by email (case-insensitive).
     */
    public function findForPassport(string $username): self|null
    {
        return self::orWhereRaw('lower(email) = lower(?)', [$username])->first();
    }

    /**
     * Check the user role if the user is a super admin.
     */
    public function isSuperAdmin(): bool
    {

        if (!$this->hasRole(RoleEnum::SUPER_ADMIN, "api")) {
            return false;
        }
        return true;
    }

    /**
     * Check the user role if the user is a admin.
     */
    public function isAdmin(): bool
    {

        if (!$this->hasRole(RoleEnum::ADMIN, "api")) {
            return false;
        }

        return true;
    }

    /**
     * Check the user role if the user is a user.
     */
    public function isUser(): bool
    {

        if (!$this->hasRole(RoleEnum::USER, "api")) {
            return false;
        }

        return true;
    }

    /**
     * Get the user's is super admin attribute.
     */
    public function getIsSuperAdminAttribute(): bool
    {
        return $this->isSuperAdmin();
    }

    /**
     * Get the user's is admin attribute.
     */
    public function getIsAdminAttribute(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Get the user's is user attribute.
     */
    public function getIsUserAttribute(): bool
    {
        return $this->isUser();
    }

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get the user's cognito id.
     */
    public function getIsCognitoUserAttribute(): bool
    {
        return $this->cognito_id !== null;
    }

    /**
     * Get the user's email.
     */
    protected function email(): Attribute
    {
        return new Attribute(
            get: static fn (string|null $value): string|null => is_null($value) ? null : strtolower($value),
        );
    }

    public function getMorphClass()
    {
        return self::MORPH_CLASS;
    }

    //============================Scopes===========================//
    public function scopeIsDisabled(Builder $query, bool $isDisabled = true): Builder
    {
        return $query->where('is_disabled', $isDisabled);
    }

    public function scopeIsCognitoUserConfirmed(Builder $query, bool $isCognitoUserConfirmed = true): Builder
    {
        return $query->where('is_cognito_user_confirmed', $isCognitoUserConfirmed);
    }

    public function scopeIsCognitoUser(Builder $query, bool $isCognitoUser = true): Builder
    {
        if ($isCognitoUser) {
            return $query->whereNotNull('cognito_id');
        }
        return $query->whereNull('cognito_id');
    }
}
