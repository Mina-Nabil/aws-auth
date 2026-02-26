<?php

namespace App\Ship\Parents\Exceptions;

use Throwable;

final class ThirdPartyException extends Exception
{
    public function __construct(string $message = 'Third Party Exception', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}