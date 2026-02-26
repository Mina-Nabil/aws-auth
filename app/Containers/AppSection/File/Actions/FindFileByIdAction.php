<?php

namespace App\Containers\AppSection\File\Actions;

use App\Containers\AppSection\File\Enums\FileGate;
use App\Ship\Parents\Exceptions\DBCallException;
use App\Containers\AppSection\File\Models\File;
use App\Containers\AppSection\File\Tasks\FindFileByIdTask;
use App\Ship\Parents\Actions\Action as ParentAction;
use Exception;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Access\AuthorizationException;

final class FindFileByIdAction extends ParentAction
{
    public function __construct(
        private readonly FindFileByIdTask $findFileByIdTask,
    ) {
    }

    /**
     * @param string $id The id of the SS file to find
     * @return File The found SS file
     * @throws DBCallException If there is an error finding the SS file
     * @throws AuthorizationException If the user is not authorized to view the SS file.
     */
    public function run(string $id): File
    {
        try {
            Log::info('Finding SS file by id: ' . $id);
            $file = $this->findFileByIdTask->run($id);
        } catch (Exception $e) {
            Log::error('Error finding SS file by id: ' . $id);
            throw new DBCallException("Error finding SS file by id", 0, $e);
        }
        Gate::authorize(FileGate::VIEW_FILE, $file);
        return $file;
    }
}
