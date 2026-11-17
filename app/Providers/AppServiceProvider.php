<?php

namespace App\Providers;

use App\Models\AuditLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Auditoría y registro de eventos de autenticación
        Event::listen(Login::class, function (Login $event): void {
            AuditLog::record(
                'auth.login',
                'Seguridad y Acceso',
                "Inicio de sesión exitoso del usuario {$event->user->name} ({$event->user->email}).",
                $event->user,
                [],
                $event->user->id
            );
        });

        Event::listen(Failed::class, function (Failed $event): void {
            $email = $event->credentials['email'] ?? 'desconocido';
            AuditLog::record(
                'auth.failed',
                'Seguridad y Acceso',
                "Intento fallido de inicio de sesión con correo '{$email}'.",
                null,
                ['email' => $email]
            );
        });

        Event::listen(Logout::class, function (Logout $event): void {
            if ($event->user) {
                AuditLog::record(
                    'auth.logout',
                    'Seguridad y Acceso',
                    "Cierre de sesión del usuario {$event->user->name} ({$event->user->email}).",
                    $event->user,
                    [],
                    $event->user->id
                );
            }
        });

        Event::listen(Lockout::class, function (Lockout $event): void {
            $email = $event->request->input('email') ?? 'desconocido';
            AuditLog::record(
                'security.lockout',
                'Seguridad y Acceso',
                "Bloqueo temporal por exceso de intentos fallidos para el usuario '{$email}'.",
                null,
                ['email' => $email, 'ip' => $event->request->ip()]
            );
        });
    }
}
