<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\ShowProcedure;
use App\Livewire\ManageCatalogs;
use App\Livewire\ManageUsers;
use App\Livewire\ReportsOverview;
use App\Http\Controllers\Api\CallWebhookController;
use App\Livewire\CallsReport;
use App\Http\Controllers\CallAudioController;


Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::view('profile', 'profile')
        ->name('profile');

    Route::view('/documentacion', 'documentation')
        ->name('system.docs');

    // Rutas operativas generales (Todos los usuarios autenticados)
    Route::get('/procedimientos/{procedure}', ShowProcedure::class)
        ->name('procedures.show');

    Route::get('/procedimientos/{procedure}/ficha', function (\App\Models\Procedure $procedure) {
        $user = auth()->user();
        if ($user && !$user->hasAnyRole(['Administrador', 'Supervisor'])) {
            $allowedSectors = $user->sectors->pluck('id')->toArray();
            if (!in_array($procedure->sector_id, $allowedSectors)) {
                abort(403, 'No tienes permisos para visualizar procedimientos de este sector.');
            }
        }
        $procedure->load(['sector', 'procedureType', 'initialProcedureType', 'device', 'origin', 'user', 'logs.user', 'evidences.user', 'patrolAssignment.crew', 'children']);
        return view('procedures.print', compact('procedure'));
    })->name('procedures.print');

    Route::get('/terreno', \App\Livewire\FieldInspectorPanel::class)
        ->name('field.inspector');

    Route::get('/mapa-operativo', \App\Livewire\LiveOperationalMap::class)
        ->name('map.live');

    Route::get('/calls/{call}/play', [CallAudioController::class, 'play'])
        ->name('calls.play');

    // Historial de llamadas, Turnos de Sala y Eventos Masivos / Contingencias: Administrador, Supervisor y Operador
    Route::middleware(['role:Administrador|Supervisor|Operador'])->group(function () {
        Route::get('/turno-sala', \App\Livewire\ManageOperatorShifts::class)
            ->name('shifts.manage');

        Route::get('/turno-sala/{shift}/reporte-email', function (\App\Models\OperatorShift $shift) {
            $shift->load(['openedBy', 'closedBy', 'receivedBy']);
            return (new \App\Mail\ShiftClosedReportMail($shift))->render();
        })->name('shifts.email-preview');

        Route::get('/reportes/llamadas', CallsReport::class)
            ->name('reports.calls');

        Route::get('/eventos-masivos', \App\Livewire\ManageOperationalEvents::class)
            ->name('events.manage');
    });

    // Gestión de Turnos de Flota, Auditoría y Reportes: Administrador y Supervisor
    Route::middleware(['role:Administrador|Supervisor'])->group(function () {
        Route::get('/reportes', ReportsOverview::class)
            ->name('reports.index');

        Route::get('/turnos', App\Livewire\ManagePatrols::class)
            ->name('patrols.manage');

        Route::get('/auditoria', \App\Livewire\AuditLogsViewer::class)
            ->name('audit.logs');
    });

    // Administración exclusiva: Administrador
    Route::middleware(['role:Administrador'])->group(function () {
        Route::get('/mantenedores', ManageCatalogs::class)
            ->name('catalogs.manage');

        Route::get('/usuarios', ManageUsers::class)
            ->name('users.manage');

        Route::get('/hitos', App\Livewire\ManageLandmarks::class)
            ->name('landmarks.manage');
    });
});

Route::post('/v1/asterisk/webhook', [CallWebhookController::class, 'handle'])
    ->middleware('throttle:120,1');

require __DIR__.'/auth.php';
