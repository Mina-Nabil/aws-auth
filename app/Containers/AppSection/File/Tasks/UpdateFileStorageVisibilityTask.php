<?php

namespace App\Containers\AppSection\File\Tasks;

use App\Containers\AppSection\File\Enums\FileVisibility;
use App\Containers\AppSection\File\Models\File;
use App\Ship\Parents\Exceptions\FileManagementException;
use App\Ship\Parents\Tasks\Task as ParentTask;
use Exception;
use Illuminate\Support\Facades\Storage;
use Webmozart\Assert\Assert;

final class UpdateFileStorageVisibilityTask extends ParentTask
{
    public function run(File $file, FileVisibility $visibility): bool
    {
        try {
            $visibility = $visibility === FileVisibility::PUBLIC ? 'public' : 'private';
            Storage::setVisibility($file->storage_path, $visibility);
            return true;
        } catch (Exception $e) {
            report($e);
            throw new FileManagementException('Error setting file visibility', previous: $e);
        }
    }
}
