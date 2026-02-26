<?php

namespace App\Containers\AppSection\File\Tasks;

use App\Containers\AppSection\File\Data\Repositories\FileRepository;
use App\Containers\AppSection\File\Data\DTOs\FileDTO;
use App\Ship\Parents\Exceptions\FileManagementException;
use App\Ship\Parents\Tasks\Task as ParentTask;
use Exception;
use Illuminate\Support\Facades\Storage;

final class UploadFileTask extends ParentTask
{
    public function run(FileDTO $fileDTO): string
    {
        try {
            $path = Storage::disk($fileDTO->storageDisk)
                ->putFile($fileDTO->initialPath, $fileDTO->file, $fileDTO->visibility);

            if ($path === false) {
                throw new FileManagementException('Failed to upload file');
            }

            return $path;
        } catch (Exception $e) {
            throw new FileManagementException('Error uploading file', previous: $e);
        }
    }
}
