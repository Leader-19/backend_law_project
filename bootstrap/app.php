<?php

use App\Http\Middleware\EncryptRouteParameters;
use App\Http\Middleware\EnforceSubscriptionLimits;
use App\Http\Middleware\EnsureAccountIsApproved;
use App\Http\Middleware\EnsureUserHasCategoryPermission;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RouteSecurity;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\ThrottleRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'category.permission' => EnsureUserHasCategoryPermission::class,
            'throttle.api' => ThrottleRequests::class,
            'subscription.limit' => EnforceSubscriptionLimits::class,
            'route.security' => RouteSecurity::class,
            'route.encrypt' => EncryptRouteParameters::class,
        ]);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            SecurityHeaders::class,
            EnsureAccountIsApproved::class,
        ]);

        $middleware->api(append: [
            ThrottleRequests::class,
            SecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
