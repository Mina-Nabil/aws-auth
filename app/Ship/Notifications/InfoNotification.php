<?php

namespace App\Ship\Notifications;

use App\Ship\Parents\Models\UserModel;
use App\Ship\Parents\Notifications\Notification as ParentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

final class InfoNotification extends ParentNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
    ) {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(UserModel $notifiable): array
    {
        return [
            "title" => $this->title,
            "message" => $this->message,
        ];
    }

    /**
     * Get the notification's database type.
     */
    public function databaseType(object $notifiable): string
    {
        return 'info';
    }
}
