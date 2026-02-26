<?php

namespace App\Containers\AppSection\File\Tasks;

use App\Containers\AppSection\File\Data\Repositories\FileRepository;
use App\Containers\AppSection\File\Data\DTOs\FileDTO;
use App\Containers\AppSection\File\Models\File;
use App\Ship\Parents\Tasks\Task as ParentTask;

final class UpdateFileRecordTask extends ParentTask
{
    public function __construct(
        private readonly FileRepository $repository,
    ) {
    }

    public function run(
        FileDTO $fileDTO
    ): File {
        return $this->repository->update($fileDTO->getUpdateArray(), $fileDTO->fileId);
    }
}
