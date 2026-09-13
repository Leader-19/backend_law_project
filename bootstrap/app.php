<?php

use App\Http\Middleware\EncryptRouteParameters;
use App\Http\Middleware\EnforceSubscriptionLimits;
use App\Http\Middleware\EnsureAccountIsApproved;
use App\Http\Middleware\EnsureUserHasCategoryPermission;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\LogApiRequest;
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

        $middleware->api(prepend: [
            LogApiRequest::class,
        ]);

        $middleware->api(append: [
            ThrottleRequests::class,
            SecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                \Illuminate\Support\Facades\Log::channel('stack')->error('Unhandled API exception', [
                    'request_id' => $request->attributes->get('request_id'),
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'user_id' => auth()->id(),
                    'ip' => $request->ip(),
                ]);

                $message = 'Something went wrong. Please try again later.';

                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'The given data was invalid.',
                        'errors' => $e->errors(),
                    ], 422);
                }

                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Unauthenticated.',
                    ], 401);
                }

                if ($e instanceof \Spatie\Permission\Exceptions\UnauthorizedException) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You do not have permission to perform this action.',
                    ], 403);
                }

                return response()->json([
                    'status' => 'error',
                    'message' => $message,
                ], 500);
            }
        });
    })->create();
