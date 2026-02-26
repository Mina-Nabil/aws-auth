<?php

namespace App\Containers\AppSection\File\Tasks;

use App\Containers\AppSection\File\Data\Repositories\FileRepository;
use App\Containers\AppSection\File\Models\File;
use App\Ship\Parents\Tasks\Task as ParentTask;

final class FindFileByIdTask extends ParentTask
{
    public function __construct(
        private readonly FileRepository $repository,
    ) {
    }

    public function run(mixed $fileId): File
    {
        return $this->repository->findOrFail($fileId);
    }
}
