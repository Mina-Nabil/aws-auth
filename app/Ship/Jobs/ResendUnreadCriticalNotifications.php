<?php

namespace App\Ship\Jobs;

use App\Ship\Parents\Jobs\Job as ParentJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Containers\AppSection\User\Models\User;
use App\Ship\Notifications\MailNotification;

final class ResendUnreadCriticalNotifications extends ParentJob implements ShouldQueue
{
    public function __construct()
    {
    }

    public function handle(): void
    {
        $mailDelay = config('notifications.mail_delay');

        $activeUsers = User::where('is_disabled', false)->get();
        foreach ($activeUsers as $user) {
            $user->unreadNotifications()
               ->where('type', 'critical')
               ->whereBetween(
                   'created_at',
                   [
                     // we add 1 hour because the job runs every hour
                     now()->subHours($mailDelay + 1),
                     now()->subHours($mailDelay)
               ]
               )
               ->get()->each(function ($notification) use ($user) {
                   $user->notify(new MailNotification($notification->data['title'], $notification->data['message']));
               });
        }
    }
}
