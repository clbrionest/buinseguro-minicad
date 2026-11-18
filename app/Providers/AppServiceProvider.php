<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Services\VersionService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(VersionService::class, fn () => new VersionService);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view): void {
            if ($view->offsetExists('cadVersionTag')) {
                return;
            }
            $versionService = app(VersionService::class);
            $view->with([
                'cadVersion' => $versionService->getVersion(),
                'cadVersionTag' => $versionService->getVersionTag(),
                'cadLatestRelease' => $versionService->getLatestRelease(),
                'cadReleases' => $versionService->getReleases(8),
                'cadRecentChanges' => $versionService->getRecentChangesSummary(5),
            ]);
        });
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
