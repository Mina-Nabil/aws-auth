<?php 

namespace App\Containers\AppSection\User\Exceptions;

use App\Ship\Parents\Exceptions\Exception as ParentException;
use Throwable;

final class CognitoUsernameExistsException extends ParentException
{
    public function __construct(string $message = 'Username already exists in AWS Cognito', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}