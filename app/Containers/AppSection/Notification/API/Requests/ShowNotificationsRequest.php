<?php

namespace App\Containers\AppSection\Notification\API\Requests;

use App\Ship\Parents\Requests\Request as ParentRequest;

final class ShowNotificationsRequest extends ParentRequest
{
    public function rules(): array
    {
        return [];
    }

    public function authorize(): bool
    {
        return true;
    }
}
