<?php

namespace App\Containers\AppSection\CognitoAuth\Exceptions;

use App\Ship\Parents\Exceptions\Exception as ParentException;
use Throwable;

final class UsernameExistsException extends ParentException
{
    public function __construct(string $message = 'A user with this email already exists in Cognito', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
