<?php

namespace App\Ship\Parents\Exceptions;

use Apiato\Core\Exceptions\HttpException as AbstractHttpException;
use Throwable;
use Symfony\Component\HttpFoundation\Response as HttpStatusCode;

class HttpException extends AbstractHttpException
{
    public function __construct($code = HttpStatusCode::HTTP_INTERNAL_SERVER_ERROR, string $message = 'HTTP Exception', ?Throwable $previous = null)
    {
        parent::__construct($code, $message, $previous);
    }
}
