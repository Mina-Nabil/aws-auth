<?php

namespace App\Containers\AppSection\User\Tasks;

use App\Containers\AppSection\User\Models\User;
use App\Ship\Parents\Tasks\Task as ParentTask;

final class ForceDeleteUserTask extends ParentTask
{
    public function run(User $user): void
    {
        $user->forceDelete();
    }
}
