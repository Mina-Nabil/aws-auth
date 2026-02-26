<?php

namespace App\Containers\AppSection\CognitoAuth\Exceptions;

use App\Ship\Parents\Exceptions\Exception as ParentException;
use Throwable;

final class UserNotFoundException extends ParentException
{
    public function __construct(string $message = 'User not found in Cognito', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
