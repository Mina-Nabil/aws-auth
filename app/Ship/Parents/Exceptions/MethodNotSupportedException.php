<?php

namespace App\Ship\Parents\Exceptions;

use Throwable;

final class MethodNotSupportedException extends Exception
{
    public function __construct(string $message = 'Method Not Supported', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}