<?php

namespace App\Containers\AppSection\User\Notifications;

use App\Containers\AppSection\User\Models\User;
use App\Ship\Parents\Notifications\Notification as ParentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

final class WelcomeNotification extends ParentNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $appUrl,
        protected ?string $temporaryPassword = null,
    ) {
    }

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $message = (new MailMessage())
            ->subject('Welcome to ' . config('app.name'))
            ->greeting('Welcome to ' . config('app.name') . ', ' . $notifiable->full_name . '!')
            ->line('Your account has been created successfully. We\'re excited to have you on board!')
            ->line('**Your Login Credentials:**')
            ->line('Email / Username: **' . $notifiable->email . '**');
        if ($this->temporaryPassword) {
            $message->line('Temporary Password: `' . $this->temporaryPassword . '`')
                ->line('**Important:** You will be asked to set a new password when you first sign in. Please keep this temporary password secure and do not share it with anyone.');
        } else {
            $message->line('Password: *************');
        }

        return $message->action('Access ' . config('app.name'), $this->appUrl)
            ->line('If you have any questions or need assistance, please don\'t hesitate to contact our support team.')
            ->salutation('Best regards, The ' . config('app.name') . ' Team');
    }
}
