<?php

namespace App\Containers\AppSection\Authentication\Providers;

use App\Containers\AppSection\Authentication\Actions\EmailVerification\GenerateUrlAction;
use App\Ship\Parents\Providers\ServiceProvider as ParentServiceProvider;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

final class EmailVerificationServiceProvider extends ParentServiceProvider
{
    public function boot(): void
    {
        VerifyEmail::createUrlUsing(new GenerateUrlAction());

        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            return (new MailMessage())
            ->subject('Welcome to ' . config('app.name'))
            ->line('Thank you ' . $notifiable->first_name . ' for registering.')
            ->line('Please use the following link to verify your email: ' . $url)
            ->line('If you did not register, please ignore this email.')
            ->line('Thank you for using our application!');
        });
    }
}
