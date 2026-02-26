<?php

namespace App\Containers\AppSection\UserTable\Models;

use App\Containers\AppSection\User\Models\User;
use App\Ship\Parents\Models\Model as ParentModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class UserTable extends ParentModel
{
    public const MORPH_CLASS = 'usertable';
    protected $table = 'user_tables';

    protected $fillable = [
        'user_id',
        'table_name',
        'columns',
    ];

    protected $casts = [
        'columns' => 'array',
    ];

    //============================Scopes===========================//

    public function scopeByUserId(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByTableName(Builder $query, string $tableName): Builder
    {
        return $query->where('table_name', $tableName);
    }

    //============================Relations===========================//

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    //============================Methods===========================//

    public function getMorphClass(): string
    {
        return self::MORPH_CLASS;
    }
}
