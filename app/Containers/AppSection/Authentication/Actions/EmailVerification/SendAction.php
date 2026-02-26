<?php

namespace App\Containers\AppSection\Authentication\Actions\EmailVerification;

use App\Ship\Parents\Actions\Action as ParentAction;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Log;

final class SendAction extends ParentAction
{
    public function run(MustVerifyEmail $user): void
    {
        Log::info('Sending verification email');
        Log::debug('User: ' . $user->email);
        if (!$user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
            Log::info('Verification email sent');
        } else {
            Log::info('User already verified');
        }
    }
}
