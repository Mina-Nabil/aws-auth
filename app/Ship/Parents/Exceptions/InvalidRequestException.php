<?php

namespace App\Ship\Parents\Exceptions;

use Throwable;

final class InvalidRequestException extends Exception
{
    public function __construct(string $message = 'Invalid Request', int $code = 1, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
