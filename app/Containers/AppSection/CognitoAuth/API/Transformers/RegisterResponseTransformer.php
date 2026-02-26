<?php

namespace App\Containers\AppSection\CognitoAuth\API\Transformers;

use App\Containers\AppSection\CognitoAuth\Models\RegisterResponse;
use App\Ship\Parents\Transformers\Transformer as ParentTransformer;

final class RegisterResponseTransformer extends ParentTransformer
{
    public function transform(RegisterResponse $registerResponse): array
    {
        return [
            'message' => $registerResponse->message,
            'user_confirmed' => $registerResponse->userConfirmed,
        ];
    }
}
