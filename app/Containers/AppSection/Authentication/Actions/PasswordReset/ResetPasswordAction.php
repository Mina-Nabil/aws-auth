<?php

namespace App\Containers\AppSection\Authentication\Actions\PasswordReset;

use App\Containers\AppSection\Authentication\UI\API\Requests\PasswordReset\ResetPasswordRequest;
use App\Containers\AppSection\User\Models\User;
use App\Ship\Parents\Actions\Action as ParentAction;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

final class ResetPasswordAction extends ParentAction
{
    public function run(ResetPasswordRequest $request): string
    {
        Log::info('Resetting password');
        Log::debug('Request: ' . json_encode($request->all()));
        $sanitizedData = $request->sanitize([
            'token',
            'email',
            'password',
            'password_confirmation',
        ]);

        $status = Password::reset(
            $sanitizedData,
            static function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            },
        );
        Log::info('Password reset');
        Log::debug('Status: ' . $status);

        return match ($status) {
            Password::PASSWORD_RESET => __($status),
            Password::INVALID_TOKEN => throw ValidationException::withMessages(['token' => __($status)]),
            default => throw ValidationException::withMessages(['email' => __($status)]),
        };
    }
}
