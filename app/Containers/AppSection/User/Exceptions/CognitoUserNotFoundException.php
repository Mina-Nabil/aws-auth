<?php 

namespace App\Containers\AppSection\User\Exceptions;

use App\Ship\Parents\Exceptions\Exception as ParentException;
use Throwable;

final class CognitoUserNotFoundException extends ParentException
{
    public function __construct(string $message = 'User not found in AWS Cognito user pool', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}