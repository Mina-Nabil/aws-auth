<?php

namespace App\Containers\AppSection\File\Actions;

use App\Containers\AppSection\File\Enums\FileGate;
use App\Containers\AppSection\File\Models\File;
use App\Containers\AppSection\File\Tasks\FindFileByIdTask;
use App\Containers\AppSection\File\Tasks\UpdateFileTask;
use App\Ship\Parents\Actions\Action as ParentAction;
use Illuminate\Support\Facades\Gate;
use Apiato\Core\Repositories\Exceptions\ResourceNotFound;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Ship\Parents\Exceptions\DBCallException;
use App\Containers\AppSection\File\Data\DTOs\FileDTO;
use Illuminate\Auth\Access\AuthorizationException;
use App\Containers\AppSection\File\Tasks\UpdateFileStorageVisibilityTask;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

final class UpdateFileAction extends ParentAction
{
    public function __construct(
        private readonly UpdateFileTask $updateFileTask,
        private readonly FindFileByIdTask $findFileByIdTask,
        private readonly UpdateFileStorageVisibilityTask $updateFileStorageVisibilityTask,
    ) {
    }

    /**
     * @param FileDTO $FileDTO The data for the File to update
     * @return File The updated File
     * @throws DBCallException If there is an error updating the file
     * @throws AuthorizationException If the file is not authorized to update the file.
     * @throws ResourceNotFound If the file is not found.
     */
    public function run(FileDTO $FileDTO): File
    {
        try {
            $file = $this->findFileByIdTask->run($FileDTO->fileId);
        } catch (ResourceNotFound $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Error loading File for update: ' . $FileDTO->fileId);
            throw new DBCallException("Error loading File for update", 0, $e);
        }

        Gate::authorize(FileGate::UPDATE_FILE, $file);

        try {
            DB::transaction(function () use (&$file, $FileDTO) {
                Log::info('Updating File: ' . $FileDTO->fileId);
                $file = $this->updateFileTask->run($FileDTO);
                if (App::isProduction()) {
                    //This function is not implemented in minIO, so we need to skip in local envs
                    $this->updateFileStorageVisibilityTask->run($file, $FileDTO->visibility);
                }
            });
        } catch (Exception $e) {
            Log::error('Error updating file: ' . $FileDTO->fileId, ["error" => $e->getMessage()]);
            throw new DBCallException("Error updating file", 0, $e);
        }
        return $file;
    }
}
