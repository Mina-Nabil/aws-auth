<?php

namespace App\Containers\AppSection\Authentication\Actions\PasswordReset;

use App\Containers\AppSection\Authentication\UI\API\Requests\PasswordReset\ForgotPasswordRequest;
use App\Ship\Parents\Actions\Action as ParentAction;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

final class ForgotPasswordAction extends ParentAction
{
    public function run(ForgotPasswordRequest $request): string
    {
        Log::info('Forgot password');
        Log::debug('Request: ' . json_encode($request->all()));
        $sanitizedData = $request->sanitize([
            'email',
        ]);

        $status = Password::sendResetLink($sanitizedData);
        Log::info('Reset link sent');
        Log::debug('Status: ' . $status);
        return match ($status) {
            Password::RESET_LINK_SENT => __($status),
            Password::RESET_THROTTLED => throw ValidationException::withMessages(['throttle' => __($status)]),
            default => throw ValidationException::withMessages(['email' => __($status)]),
        };
    }
}
