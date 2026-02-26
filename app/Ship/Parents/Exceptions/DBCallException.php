<?php

namespace App\Ship\Parents\Exceptions;

use Throwable;

final class DBCallException extends Exception
{
    public function __construct(string $message = 'DB Call Exception', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
