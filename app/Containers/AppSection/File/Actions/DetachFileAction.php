<?php

namespace App\Containers\AppSection\File\Actions;

use App\Ship\Parents\Exceptions\DBCallException;
use App\Containers\AppSection\File\Tasks\FindFileByIdTask;
use App\Ship\Parents\Actions\Action as ParentAction;
use Exception;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Ship\Parents\Exceptions\InvalidRequestException;

final class DetachFileAction extends ParentAction
{
    public function __construct(
        private readonly FindFileByIdTask $findFileByIdTask,
    ) {
    }

    /**
     * @param string $entityType The type of the entity to detach the file from
     * @param string $entityId The id of the entity to detach the file from
     * @param string $fileId The id of the file to detach
     * @return bool True if the file was detached from the entity
     * @throws InvalidRequestException If the entity type is not valid
     * @throws ModelNotFoundException If the entity is not found
     * @throws DBCallException If there is an error finding the SS file
     * @throws AuthorizationException If the user is not authorized to view the SS file.
     */
    public function run(string $entityType, string $entityId, string $fileId)
    {
        if (!array_key_exists($entityType, Relation::morphMap())) {
            throw new InvalidRequestException("Invalid entity type: " . $entityType);
        }

        $entity = Relation::morphMap()[$entityType]::findOrFail($entityId);

        Gate::authorize("detach-" . $entity->getMorphClass() . "-files-gate", $entity);
        try {
            $file = $this->findFileByIdTask->run($fileId);
        } catch (Exception $e) {
            Log::error('Error finding SS file: ' . $fileId);
            throw new DBCallException("Error finding SS file", 0, $e);
        }
        Log::info('Detaching file from entity: ' . $entity->getMorphClass());
        Log::debug('Detaching file', [
            'file' => $file->id,
            'entity' => $entity->id,
            'entity_type' => $entity->getMorphClass(),
        ]);
        try {
            $file->entityLinks()->delete([
                'entity_id' => $entity->id,
                'entity_type' => $entity->getMorphClass(),
            ]);
            Log::info('File detached from entity: ' . $entity->getMorphClass());
            return true;
        } catch (Exception $e) {
            Log::error('Error detaching file from entity: ' . $entity->getMorphClass());
            throw new DBCallException("Error detaching file from entity", 0, $e);
        }

    }
}
