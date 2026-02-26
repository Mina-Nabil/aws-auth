<?php

namespace App\Containers\AppSection\Authentication\Actions\PasswordReset;

use App\Containers\AppSection\Authentication\UI\API\Controllers\PasswordResetController;
use App\Containers\AppSection\User\Models\User;
use App\Ship\Apps\AppFactory;
use App\Ship\Parents\Actions\Action as ParentAction;
use Illuminate\Support\Facades\Log;

final class GenerateUrlAction extends ParentAction
{
    public function __invoke(User $notifiable, string $token): string
    {
        Log::info('Generating reset url');
        Log::debug('User: ' . $notifiable->email);
        Log::debug('Token: ' . $token);
        $verificationUrl = action([PasswordResetController::class, 'resetPassword'], [
            'token' => $token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        $url = AppFactory::current()->resetPasswordUrl() . '?reset_url=' . $verificationUrl;
        Log::info('Reset url generated');
        Log::debug('Url: ' . $url);

        return $url;
    }
}
