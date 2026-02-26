<?php

namespace App\Containers\AppSection\Notification\API\Requests;

use App\Ship\Parents\Requests\Request as ParentRequest;

final class UpdateNotificationsReadAtRequest extends ParentRequest
{
    public function rules(): array
    {
        return [
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'required|uuid',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
