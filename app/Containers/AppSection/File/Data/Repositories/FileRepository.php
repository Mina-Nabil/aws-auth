<?php

namespace App\Containers\AppSection\File\Data\Repositories;

use App\Containers\AppSection\File\Models\File;
use App\Ship\Parents\Repositories\Repository as ParentRepository;
use Carbon\Carbon;

/**
 * @template TModel of File
 *
 * @extends ParentRepository<TModel>
 */
final class FileRepository extends ParentRepository
{
    protected $fieldSearchable = [
        'id' => '=',
        'original_name' => 'like',
        'description' => 'like',
        'tags' => 'like',
        'mime_type' => 'like',
        'uploaded_by' => '=',
    ];

    public function loadQuery(
        ?string $q = null,
        ?string $tags = null,
        ?string $entityType = null,
        ?string $entityId = null,
        ?string $uploaderId = null,
        ?string $visibility = null,
        array $mimeTypes = [],
        ?Carbon $after = null,
        ?Carbon $before = null,
        bool $onlyTmp = false,
    ): self {
        return $this->scopeQuery(function ($query) use ($q, $tags, $entityType, $entityId, $uploaderId, $visibility, $mimeTypes, $after, $before, $onlyTmp) {
            if ($q) {
                $query = $query->byNameOrDescriptionLike($q);
            }
            if ($tags) {
                $query = $query->byTags($tags);
            }
            if ($entityType) {
                $query = $query->byEntityType($entityType);
            }
            if ($entityId) {
                $query = $query->byEntityId($entityId);
            }
            if ($uploaderId) {
                $query = $query->byUploaderId($uploaderId);
            }
            if ($visibility) {
                $query = $query->byVisibility($visibility);
            }
            if ($mimeTypes) {
                $query = $query->byMimeTypes($mimeTypes);
            }
            if ($after) {
                $query = $query->afterDate($after);
            }
            if ($before) {
                $query = $query->beforeDate($before);
            }
            if ($onlyTmp) {
                $query = $query->onlyTmp();
            }
            return $query;
        });
    }

    public function model(): string
    {
        return File::class;
    }
}
