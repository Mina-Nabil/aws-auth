<?php

namespace App\Containers\AppSection\Notification\Actions;

use App\Ship\Parents\Models\UserModel as User;
use App\Ship\Parents\Actions\Action as ParentAction;
use Apiato\Core\Repositories\Exceptions\ResourceNotFound;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Ship\Parents\Exceptions\DBCallException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

final class UpdateNotificationsReadAtAction extends ParentAction
{
    /**
     * @throws DBCallException If there is an error updating the user
     * @throws AuthorizationException If the user is not authorized to update the user.
     * @throws ResourceNotFound If the user is not found.
     */
    public function run(array $notificationIds)
    {
        try {
            Log::info('Reading notifications');
            Log::debug('Notification IDs: ' . implode(', ', $notificationIds));
            /** @var User $user */
            $user = Auth::user();
            $user->unreadNotifications()
                ->whereIn('id', $notificationIds)
                ->update(['read_at' => now()]);
        } catch (ResourceNotFound $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Error setting notifications as read: ' . $user->id);
            throw new DBCallException("Error setting notifications as read", 0, $e);
        }

        return true;
    }
}
