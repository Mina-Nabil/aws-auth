<?php

namespace App\Containers\AppSection\File\Actions;

use App\Containers\AppSection\File\Data\DTOs\FileDTO;
use App\Containers\AppSection\File\Enums\FileGate;
use App\Ship\Parents\Exceptions\DBCallException;
use App\Containers\AppSection\File\Models\File;
use App\Containers\AppSection\File\Tasks\CreateFileRecordTask;
use App\Containers\AppSection\File\Tasks\UploadFileTask;
use App\Ship\Parents\Actions\Action as ParentAction;
use App\Ship\Parents\Exceptions\FileManagementException;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

final class CreateFileAction extends ParentAction
{
    public function __construct(
        private readonly CreateFileRecordTask $createFileRecordTask,
        private readonly UploadFileTask $uploadFileTask,
    ) {
    }

    /**
     * Create a new admin user.
     *
     * @param array $data The data for the new admin user.
     * @return User The created admin user.
     * @throws DBCallException If there is an error creating the admin user.
     * @throws AuthorizationException If the user is not authorized to create a user.
     */
    public function run(FileDTO $fileDTO): File
    {

        Gate::authorize(FileGate::CREATE_FILE);

        Log::info('Uploading file');
        Log::debug('Raw File Data', $fileDTO->toArray());
        try {
            return DB::transaction(function () use ($fileDTO) {
                $file_path = $this->uploadFileTask->run($fileDTO);
                Log::info('File Storage Path', ["path" => $file_path]);
                $file = $this->createFileRecordTask->run($fileDTO, $file_path);
                Log::info('File created', ["file" => $file->id]);
                return $file;
            });
        } catch (FileManagementException $e) {
            Log::error('Error uploading file', ["error" => $e->getMessage()]);
            throw new FileManagementException("Error uploading file", 0, $e);
        } catch (Exception $e) {
            Log::error('Error creating file', ["error" => $e->getMessage()]);
            throw new DBCallException("Error creating file", 0, $e);
        }
    }
}
