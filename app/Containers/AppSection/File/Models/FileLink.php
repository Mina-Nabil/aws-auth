<?php

namespace App\Containers\AppSection\File\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Containers\AppSection\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use App\Ship\Parents\Models\Model as ParentModel;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class FileLink extends ParentModel
{
    protected $table = 'file_links';
    public $timestamps = false;

    protected $fillable = [
        'file_id',
        'entity_id',
        'entity_type'
    ];

    //============================Relations===========================//
    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }
}
