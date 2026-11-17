<?php

use App\Models\AuditLog;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'v1/asterisk/webhook',
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (Throwable $e) {
            if (
                app()->runningInConsole() ||
                $e instanceof ValidationException ||
                $e instanceof AuthenticationException ||
                $e instanceof NotFoundHttpException
            ) {
                return;
            }

            $request = request();
            $action = ($e instanceof AccessDeniedHttpException)
                ? 'security.denied'
                : 'system.exception';

            AuditLog::record(
                $action,
                'Sistema CAD',
                "Excepción en aplicación: {$e->getMessage()}",
                null,
                [
                    'exception' => get_class($e),
                    'file' => $e->getFile().':'.$e->getLine(),
                    'url' => $request?->fullUrl(),
                    'method' => $request?->method(),
                ]
            );
        });
    })->create();
