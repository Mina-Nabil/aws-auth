<?php

namespace App\Containers\AppSection\User\Tasks;

use App\Ship\Parents\Tasks\Task as ParentTask;
use Illuminate\Support\Str;

final class GenerateTemporaryPasswordTask extends ParentTask
{
    /**
     * Generate a secure temporary password.
     *
     * Requirements:
     * - 12+ characters
     * - Mixed case (upper and lower)
     * - At least one number
     * - At least one symbol
     *
     * @return string The generated temporary password
     */
    public function run(): string
    {
        return Str::password(12);
    }
}
