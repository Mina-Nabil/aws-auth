<?php

namespace App\Ship\Parents\Exceptions;

use Throwable;

final class FileManagementException extends Exception
{
    public function __construct(string $message = 'File Management Exception', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
