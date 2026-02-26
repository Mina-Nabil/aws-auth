<?php

namespace App\Containers\AppSection\User\Tasks;

use App\Containers\AppSection\User\Models\User;
use App\Containers\AppSection\User\Notifications\WelcomeNotification;
use App\Ship\Parents\Tasks\Task as ParentTask;
use Illuminate\Support\Facades\Log;

final class SendWelcomeEmailTask extends ParentTask
{
    /**
     * @param User $user
     * @param string|null $temporaryPassword
     * @return void
     */
    public function run(User $user, ?string $temporaryPassword = null): void
    {
        $user->notify(new WelcomeNotification(
            temporaryPassword: $temporaryPassword,
            appUrl: config("app.frontend_url"),
        ));

        Log::info('Welcome notification sent', [
            'user_id' => $user->id,
            'user_email' => $user->email,
        ]);
    }
}
