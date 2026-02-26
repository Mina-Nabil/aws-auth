<?php

namespace App\Containers\AppSection\File\Actions;

use App\Containers\AppSection\File\Enums\FileGate;
use App\Containers\AppSection\File\Tasks\DeleteFileFromStorageTask;
use App\Containers\AppSection\File\Tasks\DeleteFileTask;
use App\Containers\AppSection\File\Tasks\FindFileByIdTask;
use App\Ship\Parents\Exceptions\DBCallException;
use App\Ship\Parents\Actions\Action as ParentAction;
use App\Ship\Parents\Exceptions\InvalidRequestException;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

final class DeleteFileAction extends ParentAction
{
    public function __construct(
        private readonly FindFileByIdTask $findFileByIdTask,
        private readonly DeleteFileTask $deleteFileTask,
        private readonly DeleteFileFromStorageTask $deleteFileFromStorageTask,
    ) {
    }

    /**
     * @param string $id The id of the SS file to delete
     * @return bool
     * @throws InvalidRequestException
     * @throws DBCallException
     * @throws AuthorizationException
     */
    public function run(string $id): bool
    {

        $file = $this->findFileByIdTask->run($id);
        Gate::authorize(FileGate::DELETE_FILE, $file);

        try {
            DB::transaction(function () use ($file, $id) {
                Log::info('Deleting file', ["file" => $file->id]);
                $this->deleteFileTask->run($id);
                $this->deleteFileFromStorageTask->run($file);
            });
            return true;
        } catch (Exception $e) {
            Log::error('Error deleting file', ["error" => $e->getMessage()]);
            throw new DBCallException("Error deleting file", 0, $e);
        }
    }
}
