<?php

namespace App\Containers\AppSection\User\Interfaces;

use App\Containers\AppSection\User\Models\User;

interface UserDBServiceInterface
{
    public function getUserByEmail($email);
    public function disableUser(User $user);
    public function enableUser(User $user);
}
