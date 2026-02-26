<?php

namespace App\Containers\AppSection\File\Jobs;

use App\Containers\AppSection\File\Tasks\DeleteFileTask;
use App\Containers\AppSection\File\Tasks\ListFilesTask;
use App\Ship\Parents\Jobs\Job as ParentJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

final class DeleteTemporaryFilesJob extends ParentJob implements ShouldQueue
{
    public function __construct()
    {
    }

    public function handle(
        ListFilesTask $listFilesTask,
        DeleteFileTask $deleteFileTask
    ): void {
        Log::info('Starting deletion of temporary files');

        try {
            // Use ListFilesTask with paginate: false to get all temporary files
            $temporaryFiles = $listFilesTask->run(
                onlyTmp: true,
                paginate: false
            );

            // Ensure we have a Collection
            if (!$temporaryFiles instanceof Collection) {
                $temporaryFiles = collect($temporaryFiles);
            }

            $totalFiles = $temporaryFiles->count();
            $deletedCount = 0;
            $failedCount = 0;

            Log::info("Found {$totalFiles} temporary files to delete");

            /** @var \App\Containers\AppSection\File\Models\File $file */
            foreach ($temporaryFiles as $file) {
                try {
                    // Force delete (hard delete) the temporary file
                    $deleteFileTask->run($file->id, true);
                    $deletedCount++;
                    Log::debug("Deleted temporary file: {$file->id} ({$file->original_name})");
                } catch (\Exception $e) {
                    $failedCount++;
                    Log::error("Failed to delete temporary file: {$file->id}", [
                        'error' => $e->getMessage(),
                        'file_id' => $file->id,
                        'file_name' => $file->original_name,
                    ]);
                }
            }

            Log::info("Completed deletion of temporary files", [
                'total_files' => $totalFiles,
                'deleted_count' => $deletedCount,
                'failed_count' => $failedCount,
            ]);
        } catch (\Exception $e) {
            Log::error('Error during temporary files deletion job', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
