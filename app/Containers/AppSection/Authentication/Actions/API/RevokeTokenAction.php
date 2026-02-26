<?php

namespace App\Containers\AppSection\Authentication\Actions\API;

use App\Containers\AppSection\Authentication\Values\RefreshToken;
use App\Containers\AppSection\User\Models\User;
use App\Ship\Parents\Actions\Action as ParentAction;
use Illuminate\Support\Facades\Cookie as CookieFacade;
use Symfony\Component\HttpFoundation\Cookie;
use Illuminate\Support\Facades\Log;

final class RevokeTokenAction extends ParentAction
{
    public function run(User|null $user): Cookie
    {
        Log::info('Revoking token');
        Log::debug('User: ' . $user?->email);
        $user?->token()?->revoke();

        return CookieFacade::forget(RefreshToken::cookieName());
    }
}
