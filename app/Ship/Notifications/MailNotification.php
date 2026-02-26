<?php

namespace App\Ship\Notifications;

use App\Ship\Parents\Models\UserModel;
use App\Ship\Parents\Notifications\Notification as ParentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

final class MailNotification extends ParentNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail(UserModel $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject("From " . config('app.name') . ': ' . $this->title)
            ->greeting('Hi ' . $notifiable->first_name . ',')
            ->line($this->message)
            ->line("Thank you for using " . config('app.name'));
    }

    /**
     * Get the notification's database type.
     */
    public function databaseType(object $notifiable): string
    {
        return 'critical';
    }
}
