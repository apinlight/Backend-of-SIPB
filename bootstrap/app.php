<?php

// bootstrap/app.php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::group([], base_path('routes/auth.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware) {

        // ✅ Global middleware stack - CORS first to ensure it always runs
        $middleware->append([
            \App\Http\Middleware\CorsMiddleware::class,
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \App\Http\Middleware\TrustProxies::class,
            \App\Http\Middleware\ForceHttpsInProduction::class,
            \App\Http\Middleware\SecurityHeadersMiddleware::class,
            // \Illuminate\Http\Middleware\HandleCors::class,
        ]);

        // ✅ Route-specific middleware aliases
        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,

            // ✅ NEW middleware aliases
            'api.version' => \App\Http\Middleware\ApiVersionMiddleware::class,
            'api.validate' => \App\Http\Middleware\RequestValidationMiddleware::class,
            'api.rate_limit' => \App\Http\Middleware\ApiRateLimitMiddleware::class,
            'cors.custom' => \App\Http\Middleware\CorsMiddleware::class,

            // ✅ Development middleware
            'debug' => \App\Http\Middleware\DebugMiddleware::class,
        ]);

        // ✅ Middleware groups for different API access levels
        $middleware->group('api.public', [
            'api.validate',
            'api.rate_limit:30,1', // 30 requests per minute for public
        ]);

        $middleware->group('api.protected', [
            'api.validate',
            'api.rate_limit:100,1', // 100 requests per minute for authenticated
            'auth:sanctum',
        ]);

        $middleware->group('api.user', [
            'api.validate',
            'api.rate_limit:100,1',
            'auth:sanctum',
            'role:user', // ✅ Users and managers can create pengajuan
        ]);

        $middleware->group('api.manager', [
            'api.validate',
            'api.rate_limit:150,1', // Higher limit for managers
            'auth:sanctum',
            'role:manager', // ✅ Managers and admins
        ]);

        $middleware->group('api.admin', [
            'api.validate',
            'api.rate_limit:200,1', // Highest limit for admins
            'auth:sanctum',
            'role:admin',
        ]);

        // ✅ Development group (only works when APP_DEBUG=true)
        $middleware->group('api.debug', [
            'debug',
            'api.validate',
            'api.rate_limit:1000,1', // High limit for debugging
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {

        // ✅ API Exception Handling
        $exceptions->renderable(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        $exceptions->renderable(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated. Please login.',
                ], 401);
            }
        });

        $exceptions->renderable(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized. Insufficient permissions.',
                ], 403);
            }
        });

        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Resource not found',
                ], 404);
            }
        });

        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Method not allowed',
                    'allowed_methods' => $e->getHeaders()['Allow'] ?? [],
                ], 405);
            }
        });

        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Too many requests. Please slow down.',
                    'retry_after' => $e->getHeaders()['Retry-After'] ?? null,
                ], 429);
            }
        });

        $exceptions->renderable(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Record not found',
                ], 404);
            }
        });

        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => $e->getMessage() ?: 'Server Error',
                ], $e->getStatusCode());
            }
            
            // ✅ For non-API routes, also return JSON instead of Blade view
            return response()->json([
                'status' => false,
                'message' => $e->getMessage() ?: 'Server Error',
            ], $e->getStatusCode());
        });

    })->create();
