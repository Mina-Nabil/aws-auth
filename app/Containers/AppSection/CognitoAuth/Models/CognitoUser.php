<?php

namespace App\Containers\AppSection\CognitoAuth\Models;

use App\Ship\Parents\Models\UserModel as ParentUserModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

final class CognitoUser extends ParentUserModel
{
    use HasUuids;
    use SoftDeletes;
    use Notifiable;

    public const MORPH_CLASS = 'cognito_user';

    protected $fillable = [
        'cognito_id',
    ];
}
