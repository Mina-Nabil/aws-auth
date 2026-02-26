<?php

namespace App\Containers\AppSection\File\Tasks;

use Apiato\Core\Repositories\Exceptions\ResourceNotFound;
use App\Containers\AppSection\File\Data\Repositories\FileRepository;
use App\Containers\AppSection\File\Models\File;
use App\Ship\Parents\Exceptions\DBCallException;
use App\Ship\Parents\Tasks\Task as ParentTask;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class DeleteFileTask extends ParentTask
{
    public function __construct(
        private readonly FileRepository $repository,
    ) {
    }

    public function run(string $id, bool $hardDelete = false): bool
    {
        if ($hardDelete) {
            try {
                return File::findOrFail($id)->forceDelete();
            } catch (ModelNotFoundException $e) {
                throw new ResourceNotFound(404, "File not found");
            } catch (Exception $e) {
                throw new DBCallException("Error deleting file", 0, $e);
            }
        }
        return $this->repository->delete($id);
    }
}
