<?php

namespace App\Ship\Providers;

use Apiato\Support\Facades\Response;
use App\Containers\AppSection\User\Models\User;
use App\Ship\Parents\Models\Model;
use App\Ship\Parents\Models\UserModel;
use App\Ship\Parents\Providers\ServiceProvider as ParentServiceProvider;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Request;

final class ShipServiceProvider extends ParentServiceProvider
{
    public function boot(): void
    {
        $this->registerMacros();
        RequestException::dontTruncate();
        Date::use(CarbonImmutable::class);
        Model::shouldBeStrict(!app()->isProduction());
        UserModel::shouldBeStrict(!app()->isProduction());
    }

    public function registerMacros(): void
    {
        Relation::morphMap([
            User::MORPH_CLASS => User::class,
        ]);

        /*
         * Get the App-Identifier header value from the request or use the default app.
         */
        Request::macro('appId', function (): string {
            return $this->header('App-Identifier', config()->string('apiato.defaults.app'));
        });

        Response::macro('invalidRequest', function (string $message = "Invalid request") {
            return Response::json(["message" => $message], HttpResponse::HTTP_UNPROCESSABLE_ENTITY);
        });
        Response::macro('badRequest', function (string $message = "Bad request") {
            return Response::json(["message" => $message], HttpResponse::HTTP_BAD_REQUEST);
        });
        Response::macro('serverError', function (string $message = "Internal server error") {
            return Response::json(["message" => $message], HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        });
        Response::macro('forbidden', function (string $message = "Forbidden request") {
            return Response::json(["message" => $message], HttpResponse::HTTP_FORBIDDEN);
        });
        Response::macro('notFound', function (string $message = "Resource not found") {
            return Response::json(["message" => $message], HttpResponse::HTTP_NOT_FOUND);
        });
        Response::macro('unauthorized', function (string $message = "Unauthorized request") {
            return Response::json(["message" => $message], HttpResponse::HTTP_UNAUTHORIZED);
        });
    }
}
