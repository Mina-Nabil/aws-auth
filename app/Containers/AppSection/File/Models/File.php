<?php

namespace App\Containers\AppSection\File\Models;

use App\Containers\AppSection\File\Enums\FileVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Containers\AppSection\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Ship\Parents\Models\Model as ParentModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use OwenIt\Auditing\Contracts\Auditable;

final class File extends ParentModel implements Auditable
{
    use SoftDeletes;
    use HasUuids;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'files';

    protected $fillable = [
        'original_name',
        'storage_disk',
        'storage_path',
        'checksum',
        'mime_type',
        'size_bytes',
        'visibility',
        'uploaded_by',
        'description',
        'tags',
        'is_tmp',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_tmp' => 'boolean',
    ];

    //============================Model Methods===========================//

    public function isLinkedTo(Model $entity): bool
    {
        return $this->entityLinks()
            ->where('entity_id', $entity->id)
            ->where('entity_type', $entity->getMorphClass())
            ->exists();
    }

    public function getSignedUrl(): string
    {
        return $this->visibility === FileVisibility::PUBLIC ?
        Storage::disk($this->storage_disk)->url($this->storage_path) :
        Storage::disk($this->storage_disk)->temporaryUrl($this->storage_path, now()->addMinutes((int)config('appSection-file.temporary_url_duration')));
    }

    //============================Scopes===========================//

    public function scopeByNameOrDescriptionLike(Builder $query, $queryString): Builder
    {
        return $query->where(function ($query) use ($queryString) {
            $query->where('original_name', 'like', '%' . $queryString . '%')
                ->orWhere('description', 'like', '%' . $queryString . '%');
        });
    }

    public function scopeByTags(Builder $query, string $tags): Builder
    {
        $queryString = explode(',', $tags);
        $query->where(function ($query) use ($queryString) {
            foreach ($queryString as $tag) {
                $query->orWhereJsonContains('tags', $tag);
            }
        });
        return $query;
    }

    public function scopeByMimeType(Builder $query, $mimeType): Builder
    {
        return $query->where('mime_type', 'like', '%' . $mimeType . '%');
    }

    public function scopeByMimeTypes(Builder $query, array $mimeTypes): Builder
    {
        return $query->whereIn('mime_type', $mimeTypes);
    }

    public function scopeByUploaderId(Builder $query, $uploaderId): Builder
    {
        return $query->where('uploaded_by', $uploaderId);
    }

    public function scopeByEntityType(Builder $query, $entityType): Builder
    {
        if (!joined($query, 'file_links')) {
            $query->join('file_links', 'files.id', '=', 'file_links.file_id');
        }
        return $query->where('file_links.entity_type', $entityType);
    }

    public function scopeByEntityId(Builder $query, $entityId): Builder
    {
        if (!joined($query, 'file_links')) {
            $query->join('file_links', 'files.id', '=', 'file_links.file_id');
        }
        return $query->where('file_links.entity_id', $entityId);
    }

    public function scopeAfterDate(Builder $query, Carbon $date): Builder
    {
        return $query->where('created_at', '>=', $date->format('Y-m-d 00:00:00'));
    }

    public function scopeBeforeDate(Builder $query, Carbon $date): Builder
    {
        return $query->where('created_at', '<=', $date->format('Y-m-d 23:59:59'));
    }

    public function scopeOnlyTmp(Builder $query): Builder
    {
        return $query->where('is_tmp', true);
    }

    //============================Relations===========================//

    /**
     * Get the user that uploaded the file.
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function entityLinks(): HasMany
    {
        return $this->hasMany(FileLink::class, 'file_id');
    }


    //============================Configurations===========================//

    public function getMorphClass()
    {
        return 'file';
    }
}
