<?php

namespace App\Containers\AppSection\Authentication\Actions\EmailVerification;

use App\Containers\AppSection\User\Models\User;
use App\Ship\Apps\AppFactory;
use App\Ship\Parents\Actions\Action as ParentAction;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;

final class GenerateUrlAction extends ParentAction
{
    public function __invoke(User $notifiable): string
    {
        Log::info('Generating verification url');
        Log::debug('User: ' . $notifiable->email);
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Date::now()->addMinutes(config()->integer('auth.verification.expire', 60)),
            [
                'id' => $notifiable->id,
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
        );
        Log::info('Verification url generated');
        Log::debug('Verification url: ' . $verificationUrl);

        return urlencode(AppFactory::current()->verifyEmailUrl() . '?verification_url=' . $verificationUrl);
    }
}
