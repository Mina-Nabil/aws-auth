<?php

namespace App\Containers\AppSection\File\Actions;

use Apiato\Http\Resources\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Access\AuthorizationException;
use App\Containers\AppSection\File\Tasks\ListFilesTask;
use App\Ship\Parents\Exceptions\DBCallException;
use App\Ship\Parents\Actions\Action as ParentAction;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Containers\AppSection\File\Enums\FileType;
use App\Ship\Parents\Exceptions\InvalidRequestException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

final class ListEntityFilesAction extends ParentAction
{
    public function __construct(
        private readonly ListFilesTask $listFilesTask,
    ) {
    }

    /**
     * @return LengthAwarePaginator|Collection
     * @throws AuthorizationException If the user is not authorized to view all files.
     * @throws ModelNotFoundException If the entity is not found.
     * @throws DBCallException If there is an error listing the users.
     */
    public function run(string $entityType, string $entityId, ?string $type = null): LengthAwarePaginator|Collection
    {
        if (!array_key_exists($entityType, Relation::morphMap())) {
            throw new InvalidRequestException("Invalid entity type: " . $entityType);
        }

        $entity = Relation::morphMap()[$entityType]::findOrFail($entityId);
        Gate::authorize("list-" . $entity->getMorphClass() . "-files-gate", $entity);
        try {
            Log::info('Listing entity SS files');
            Log::debug('Entity parameters: ' . json_encode([
                'entityType' => $entityType,
                'entityId' => $entityId,
                'fileType' => $type,
            ]));
            return $this->listFilesTask->run(
                entityType: $entityType,
                entityId: $entityId,
                mimeTypes: $type ? FileType::getMimeTypes($type) : [],
            );
        } catch (Exception $e) {
            Log::error('Error listing entity SS files: ' . $e->getMessage());
            throw new DBCallException("Error listing entity SS files", 0, $e);
        }
    }
}
