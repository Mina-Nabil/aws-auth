<?php

namespace App\Containers\AppSection\User\Middleware;

use App\Ship\Parents\Middleware\Middleware as ParentMiddleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequirePasswordReset extends ParentMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->password_reset_required) {
            return response()->json([
                'message' => 'Password reset required. Please update your password before accessing other resources.',
            ], 403);
        }

        return $next($request);
    }
}
