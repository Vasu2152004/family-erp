<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: false, // Disable default health check, using custom one
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/health.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant' => \App\Http\Middleware\EnsureTenantAccess::class,
        ]);
        $middleware->trustProxies(
            '*',
            Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT |
            Request::HEADER_X_FORWARDED_PROTO 
        );
        
        // Add performance headers globally
        $middleware->append(\App\Http\Middleware\AddPerformanceHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Log all exceptions with context
        $exceptions->report(function (\Throwable $e) {
            if (app()->bound('log')) {
                \Log::error('Exception occurred', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'url' => request()->fullUrl(),
                    'method' => request()->method(),
                    'ip' => request()->ip(),
                    'user_id' => auth()->id(),
                ]);
            }
        });

        // Handle different exception types
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->guest(route('login'));
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        // In production, don't expose detailed error information
        if (config('app.env') === 'production') {
            $exceptions->render(function (\Throwable $e, $request) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Server Error',
                    ], 500);
                }
            });
        }
    })->create();
