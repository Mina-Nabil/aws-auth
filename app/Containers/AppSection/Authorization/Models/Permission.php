<?php

namespace App\Containers\AppSection\Authorization\Models;

use Apiato\Core\Models\InteractsWithApiato;
use Apiato\Http\Resources\ResourceKeyAware;
use App\Containers\AppSection\Authorization\Data\Collections\PermissionCollection;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

final class Permission extends SpatiePermission implements ResourceKeyAware
{
    use InteractsWithApiato;
    use HasUuids;

    protected $fillable = [
        'id',
        'name',
        'guard_name',
        'display_name',
        'description',
        'group',
        'is_active',
        'version',
    ];

    public function getIdAttribute(): string
    {
        return $this->attributes['id'];
    }

    public function newCollection(array $models = []): PermissionCollection
    {
        return new PermissionCollection($models);
    }
}
