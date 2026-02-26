<?php

use Apiato\Foundation\Apiato;
use Apiato\Foundation\Configuration\Routing;
use Apiato\Http\Middleware\ProcessETag;
use Apiato\Http\Middleware\ValidateJsonContent;
use App\Containers\AppSection\Authentication\UI\WEB\Controllers\HomePageController;
use App\Containers\AppSection\Authentication\UI\WEB\Controllers\LoginController;
use App\Containers\AppSection\User\Middleware\RequirePasswordReset;
use App\Ship\Jobs\ResendUnreadCriticalNotifications;
use App\Ship\Middleware\ValidateAppId;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;

use function Illuminate\Filesystem\join_paths;

$basePath = dirname(__DIR__);
$apiato = Apiato::configure(basePath: $basePath)
    ->withRouting(static function (Routing $routing) {
        $routing->loadApiRoutesFrom('app/Containers/*/*/API/Routes', 'app/Containers/*/Authentication/UI/API/Routes')
        ->prefixApiUrlsWith('api/');
    })->withCommands(
        shared_path('Commands'),
        ...\Safe\glob(join_paths($basePath, 'app/Containers/*/*/CLI')),
    )
    ->create();

return Application::configure(basePath: $basePath)
    ->withProviders($apiato->providers())
    ->withEvents($apiato->events())
    ->withRouting(
        web: $apiato->webRoutes(),
        channels: __DIR__ . '/../app/Ship/Broadcasting/channels.php',
        health: '/api/up',
        then: static fn() => $apiato->registerApiRoutes(),
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->use([
            HandleCors::class,
            ValidateAppId::class,
        ]);

        $middleware
            ->api(append: [
                ValidateJsonContent::class,
                ProcessETag::class,
            ]);

        $middleware->group('auth:api', [
            Authenticate::class . ':api',
            RequirePasswordReset::class,
        ]);

        $middleware->redirectUsersTo(static function (Request $request): string {
            return action(HomePageController::class);
        });
        $middleware->redirectGuestsTo(static function (Request $request): string {
            return action([LoginController::class, 'showForm']);
        });
    })
    ->withCommands($apiato->commands())
    ->withExceptions(static function (Exceptions $exceptions) {})
    ->withSchedule(static function (Schedule $schedule) {
        //make sure the job runs every hour to match the mail delay query
        $schedule->job(new ResendUnreadCriticalNotifications())->hourly();
    })
    ->create();
