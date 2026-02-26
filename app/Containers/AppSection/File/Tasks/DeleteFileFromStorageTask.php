<?php

namespace App\Containers\AppSection\File\Tasks;

use App\Containers\AppSection\File\Models\File;
use App\Ship\Parents\Exceptions\FileManagementException;
use App\Ship\Parents\Tasks\Task as ParentTask;
use Exception;
use Illuminate\Support\Facades\Storage;

final class DeleteFileFromStorageTask extends ParentTask
{
    public function run(File|string $file): bool
    {
        try {
            $path = $file instanceof File ? $file->storage_path : $file;
            Storage::delete($path);
            return true;
        } catch (Exception $e) {
            throw new FileManagementException('Error deleting file from storage', previous: $e);
        }
    }
}
