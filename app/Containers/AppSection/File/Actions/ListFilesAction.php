<?php

namespace App\Containers\AppSection\File\Actions;

use Apiato\Http\Resources\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Access\AuthorizationException;
use App\Containers\AppSection\File\Enums\FileGate;
use App\Containers\AppSection\File\Enums\FileType;
use App\Containers\AppSection\File\Tasks\ListFilesTask;
use App\Ship\Parents\Exceptions\DBCallException;
use App\Ship\Parents\Actions\Action as ParentAction;
use Carbon\Carbon;
use Exception;

final class ListFilesAction extends ParentAction
{
    public function __construct(
        private readonly ListFilesTask $listFilesTask,
    ) {
    }

    /**
     * @return LengthAwarePaginator|Collection
     * @throws AuthorizationException If the user is not authorized to view all files.
     * @throws DBCallException If there is an error listing the users.
     */
    public function run(?string $q = null, ?string $tags = null, ?FileType $fileType = null, ?string $uploaderId = null, ?string $entityType = null, ?string $entityId = null, ?Carbon $after = null, ?Carbon $before = null): LengthAwarePaginator|Collection
    {
        Gate::authorize(FileGate::VIEW_ANY_FILE);
        try {
            Log::info('Listing SS files');
            Log::debug('Query parameters: ' . json_encode([
                'q' => $q,
                'tags' => $tags,
                'fileType' => $fileType,
                'uploaderId' => $uploaderId,
                'entityType' => $entityType,
                'entityId' => $entityId,
                'after' => $after,
                'before' => $before,
            ]));
            return $this->listFilesTask->run(
                q: $q,
                tags: $tags,
                mimeTypes: $fileType ? FileType::getMimeTypes($fileType) : [],
                uploaderId: $uploaderId,
                entityType: $entityType,
                entityId: $entityId,
                after: $after,
                before: $before,
            );
        } catch (Exception $e) {
            Log::error('Error listing SS files: ' . $e->getMessage());
            throw new DBCallException("Error listing SS files", 0, $e);
        }
    }
}
