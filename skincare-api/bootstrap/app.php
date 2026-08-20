<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Laravel already renders AuthenticationException (401) and
        // ValidationException (422) cleanly on its own, with or without
        // APP_DEBUG — nothing to do for those. Everything else (403 aborts,
        // 404s, 405s, and genuine 500s) falls through to the framework's
        // raw debug renderer when APP_DEBUG=true, which dumps full stack
        // traces and file paths into the JSON response. This single
        // handler normalizes all of those into the same {"message": "..."}
        // shape the rest of the API already uses, and never leaks a trace
        // over HTTP — real errors still go to storage/logs/laravel.log.
        //
        // Note: Laravel converts ModelNotFoundException into
        // NotFoundHttpException internally before this callback ever sees
        // it (wrapping the original "No query results for model [...]"
        // message), so there's no way to special-case "record not found"
        // vs. "route not found" here — both are just a plain 404, which is
        // fine, since a generic "Resource not found." is the right
        // response either way and avoids leaking the model's class name.
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'message' => 'Authentication required. Please sign in as an administrator.',
                ], 401);
            }

            if ($e instanceof ValidationException) {
                return null;
            }

            $status = match (true) {
                $e instanceof ModelNotFoundException, $e instanceof NotFoundHttpException => 404,
                $e instanceof HttpExceptionInterface => $e->getStatusCode(),
                default => 500,
            };

            $message = match (true) {
                $status === 404 => 'Resource not found.',
                $status === 500 => config('app.debug') ? $e->getMessage() : 'Server error. Please try again later.',
                default => $e->getMessage() ?: 'An error occurred.',
            };

            return response()->json(['message' => $message], $status);
        });
    })->create();
