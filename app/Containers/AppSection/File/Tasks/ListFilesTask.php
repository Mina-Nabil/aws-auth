<?php

namespace App\Containers\AppSection\File\Tasks;

use App\Containers\AppSection\File\Data\Repositories\FileRepository;
use App\Containers\AppSection\File\Enums\FileVisibility;
use App\Containers\AppSection\File\Models\File;
use App\Ship\Parents\Tasks\Task as ParentTask;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class ListFilesTask extends ParentTask
{
    public function __construct(
        private readonly FileRepository $repository,
    ) {
    }

    public function run(
        ?string $q = null,
        ?string $tags = null,
        ?string $entityType = null,
        ?string $entityId = null,
        ?string $uploaderId = null,
        ?FileVisibility $visibility = null,
        array $mimeTypes = [],
        ?Carbon $after = null,
        ?Carbon $before = null,
        bool $onlyTmp = false,
        bool $paginate = true,
    ): LengthAwarePaginator|Collection {
        $query = $this->repository->loadQuery(
            q: $q,
            tags: $tags,
            entityType: $entityType,
            entityId: $entityId,
            uploaderId: $uploaderId,
            visibility: $visibility,
            mimeTypes: $mimeTypes,
            after: $after,
            before: $before,
            onlyTmp: $onlyTmp,
        )->addRequestCriteria();

        return $paginate ? $query->paginate() : $query->get();
    }
}
