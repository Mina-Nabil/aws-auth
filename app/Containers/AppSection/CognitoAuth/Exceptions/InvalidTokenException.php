<?php

namespace App\Containers\AppSection\CognitoAuth\Exceptions;

use App\Ship\Parents\Exceptions\Exception as ParentException;
use Throwable;

final class InvalidTokenException extends ParentException
{
    public function __construct(string $message = 'Invalid Token', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}