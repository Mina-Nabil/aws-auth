<?php

namespace App\Containers\AppSection\UserTable\API\Transformers;

use App\Containers\AppSection\UserTable\Models\UserTable;
use App\Ship\Parents\Transformers\Transformer as ParentTransformer;

final class UserTableTransformer extends ParentTransformer
{
    protected array $availableIncludes = [];

    protected array $defaultIncludes = [];

    public function transform(UserTable $entity): array
    {
        return [
            'visible_columns' => $entity->columns ?? [],
        ];
    }
}
