<?php

namespace App\Containers\AppSection\Notification\Actions;

use App\Ship\Parents\Exceptions\DBCallException;
use App\Ship\Parents\Actions\Action as ParentAction;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;

final class ShowNotificationsAction extends ParentAction
{
    /**
     * @return LengthAwarePaginator|Collection
     * @throws AuthorizationException If the user is not authorized to view all notifications.
     * @throws DBCallException If there is an error listing the notifications.
     */
    public function run(): LengthAwarePaginator|Collection
    {
        try {
            Log::info('Listing notifications');
            /** @var User $user */
            $user = Auth::user();
            return $user->notifications()
            ->latest()->paginate();
        } catch (Exception $e) {
            Log::error('Error listing notifications: ' . $e->getMessage());
            throw new DBCallException("Error listing notifications", 0, $e);
        }
    }
}
