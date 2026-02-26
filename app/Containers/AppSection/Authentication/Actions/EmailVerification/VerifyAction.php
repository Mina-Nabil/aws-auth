<?php

namespace App\Containers\AppSection\Authentication\Actions\EmailVerification;

use App\Ship\Parents\Actions\Action as ParentAction;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Log;

final class VerifyAction extends ParentAction
{
    public function run(MustVerifyEmail $user): void
    {
        Log::info('Verifying email');
        Log::debug('User: ' . $user->email);
        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            Log::info('Email verified');
            event(new Verified($user));
            Log::info('Email verified event dispatched');
        } else {
            Log::info('Email already verified');
        }
    }
}
