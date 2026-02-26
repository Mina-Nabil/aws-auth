<?php

namespace App\Containers\AppSection\User\Tasks;

use App\Containers\AppSection\User\Data\Repositories\UserRepository;
use App\Containers\AppSection\User\Models\User;
use App\Containers\AppSection\User\Data\DTOs\UserDTO;
use App\Ship\Parents\Tasks\Task as ParentTask;
use Illuminate\Support\Facades\Hash;

final class UpdateUserTask extends ParentTask
{
    public function __construct(
        private readonly UserRepository $repository,
    ) {
    }

    public function run(UserDTO $userDTO): User
    {
        $data = $userDTO->getUpdateArray();
        if (array_key_exists('password', $data)) {
            $data['password'] = Hash::make($data['password']);
        }
        return $this->repository->update($data, $userDTO->userId);
    }
}
