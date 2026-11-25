<?php

use App\Http\Controllers\Api\CallWebhookController;
use App\Http\Controllers\CallAudioController;
use App\Livewire\AuditLogsViewer;
use App\Livewire\CallsReport;
use App\Livewire\FieldInspectorPanel;
use App\Livewire\LiveOperationalMap;
use App\Livewire\ManageCatalogs;
use App\Livewire\ManageLandmarks;
use App\Livewire\ManageOperationalEvents;
use App\Livewire\ManageOperatorShifts;
use App\Livewire\ManagePatrols;
use App\Livewire\ManagePreventivePatrols;
use App\Livewire\ManageUsers;
use App\Livewire\OperatorReports;
use App\Livewire\ReportsOverview;
use App\Livewire\ShowPreventivePatrol;
use App\Livewire\ShowProcedure;
use App\Mail\ShiftClosedReportMail;
use App\Models\AuditLog;
use App\Models\OperatorShift;
use App\Models\PreventivePatrol;
use App\Models\Procedure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    if (auth()->user()?->isInspectorOnly()) {
        return redirect()->route('field.inspector');
    }

    return redirect()->route('dashboard');
});

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::view('profile', 'profile')
        ->name('profile');

    Route::post('/profile/theme', function (Request $request) {
        $validated = $request->validate([
            'theme' => ['required', 'string', 'in:light,dark,system'],
        ]);
        $request->user()->update(['theme_preference' => $validated['theme']]);

        return response()->json(['status' => 'ok', 'theme' => $validated['theme']]);
    })->name('profile.theme');

    Route::view('/documentacion', 'documentation')
        ->name('system.docs');

    // Impresión agrupada en un único PDF (debe declararse antes de /procedimientos/{procedure})
    Route::get('/procedimientos/imprimir-lote', function (Request $request) {
        $user = auth()->user();
        $rawIds = (string) $request->query('ids', '');
        $ids = array_values(array_unique(array_filter(array_map('intval', explode(',', $rawIds)))));

        if (empty($ids)) {
            abort(422, 'Debe seleccionar al menos un procedimiento para emitir el informe agrupado.');
        }

        $procedures = Procedure::with(['sector', 'procedureType', 'initialProcedureType', 'device', 'origin', 'user', 'logs.user', 'evidences.user', 'patrolAssignment.crew', 'children'])
            ->whereIn('id', array_slice($ids, 0, 50))
            ->orderBy('created_at', 'desc')
            ->get()
            ->filter(fn (Procedure $p) => $user && $user->canAccessProcedure($p))
            ->values();

        if ($procedures->isEmpty()) {
            abort(403, 'No tienes permisos para visualizar los procedimientos seleccionados.');
        }

        $watermark = $request->boolean('watermark', true);

        AuditLog::record(
            'procedure.export',
            'Reportes',
            "Emisión de informe PDF agrupado con {$procedures->count()} procedimiento(s).",
            null,
            [
                'format' => 'batch_pdf',
                'procedure_ids' => $procedures->pluck('id')->toArray(),
                'watermark' => $watermark,
            ]
        );

        return view('procedures.print-batch', [
            'procedures' => $procedures,
            'watermark' => $watermark,
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ]);
    })->name('procedures.print-batch');

    // Rutas operativas generales (Todos los usuarios autenticados)
    Route::get('/procedimientos/{procedure}', ShowProcedure::class)
        ->name('procedures.show');

    Route::get('/procedimientos/{procedure}/ficha', function (Procedure $procedure) {
        $user = auth()->user();
        if ($user && ! $user->canAccessProcedure($procedure)) {
            abort(403, 'No tienes permisos para visualizar este procedimiento.');
        }
        $procedure->load(['sector', 'procedureType', 'initialProcedureType', 'device', 'origin', 'user', 'logs.user', 'evidences.user', 'patrolAssignment.crew', 'children']);

        return view('procedures.print', compact('procedure'));
    })->name('procedures.print');

    Route::get('/terreno', FieldInspectorPanel::class)
        ->name('field.inspector');

    Route::redirect('/modo-terreno', '/terreno');

    Route::get('/mapa-operativo', LiveOperationalMap::class)
        ->name('map.live');

    Route::get('/patrullajes-preventivos', ManagePreventivePatrols::class)
        ->name('patrols.preventive');

    Route::get('/patrullajes-preventivos/{patrol}', ShowPreventivePatrol::class)
        ->name('patrols.preventive.show');

    Route::get('/patrullajes-preventivos/{patrol}/imprimir', function (PreventivePatrol $patrol) {
        $user = auth()->user();
        if ($user && ! $user->hasAnyRole(['Administrador', 'Supervisor'])) {
            $allowedSectors = $user->sectors->pluck('id')->toArray();
            if (! in_array($patrol->sector_id, $allowedSectors)) {
                abort(403, 'No tienes permisos para visualizar patrullajes de este sector.');
            }
        }
        $patrol->load(['device', 'sector', 'user', 'patrolAssignment.crew', 'checkpoints.landmark', 'checkpoints.validatedBy', 'procedures.procedureType']);

        return view('patrols.print', compact('patrol'));
    })->name('patrols.preventive.print');

    Route::get('/calls/{call}/play', [CallAudioController::class, 'play'])
        ->name('calls.play');

    // Historial de llamadas, Turnos de Sala y Eventos Masivos / Contingencias: Administrador, Supervisor y Operador
    Route::middleware(['role:Administrador|Supervisor|Operador'])->group(function () {
        Route::get('/turno-sala', ManageOperatorShifts::class)
            ->name('shifts.manage');

        Route::get('/turno-sala/{shift}/reporte-email', function (OperatorShift $shift) {
            $shift->load(['openedBy', 'closedBy', 'receivedBy']);

            return (new ShiftClosedReportMail($shift))->render();
        })->name('shifts.email-preview');

        Route::get('/reportes/llamadas', CallsReport::class)
            ->name('reports.calls');

        Route::get('/mis-reportes', OperatorReports::class)
            ->name('reports.operator');

        Route::get('/eventos-masivos', ManageOperationalEvents::class)
            ->name('events.manage');
    });

    // Gestión de Turnos de Flota, Auditoría y Reportes: Administrador y Supervisor
    Route::middleware(['role:Administrador|Supervisor'])->group(function () {
        Route::get('/reportes', ReportsOverview::class)
            ->name('reports.index');

        Route::get('/turnos', ManagePatrols::class)
            ->name('patrols.manage');

        Route::get('/auditoria', AuditLogsViewer::class)
            ->name('audit.logs');
    });

    // Administración exclusiva: Administrador
    Route::middleware(['role:Administrador'])->group(function () {
        Route::get('/mantenedores', ManageCatalogs::class)
            ->name('catalogs.manage');

        Route::get('/usuarios', ManageUsers::class)
            ->name('users.manage');

        Route::get('/hitos', ManageLandmarks::class)
            ->name('landmarks.manage');
    });
});

Route::post('/v1/asterisk/webhook', [CallWebhookController::class, 'handle'])
    ->middleware('throttle:120,1');

require __DIR__.'/auth.php';
