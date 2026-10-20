<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Procedure;
use App\Models\Sector;
use Livewire\Attributes\On;

class ActiveProcedures extends Component
{
    public $search = '';
    public $filterStatus = '';
    public $filterSector = '';

    public function toggleStatusFilter($status)
    {
        $this->filterStatus = ($this->filterStatus === $status) ? '' : $status;
    }

    public function updateDeviceStatus($deviceId, $newStatus)
    {
        $allowed = \App\Models\SystemCatalogItem::namesFor('device_statuses', [
            'Disponible (QAP)',
            'En Colación',
            'Punto Fijo',
            'En Mantención',
        ]);
        if (!in_array($newStatus, $allowed)) {
            return;
        }

        \App\Models\Device::where('id', $deviceId)->update([
            'operational_status' => $newStatus,
        ]);
    }

    // Esta función se ejecuta cada vez que el componente se carga o actualiza
    #[On('procedure-created')]
    public function render()
    {
        $user = auth()->user();

        // 1. Iniciamos la consulta base (Excluimos Cerrados, Cancelados y Vinculados)
        $baseQuery = Procedure::with(['sector', 'procedureType', 'initialProcedureType', 'device', 'origin'])
            ->whereNotIn('status', ['Cerrado', 'Cancelado', 'Vinculado'])
            ->orderBy('created_at', 'desc');

        // 2. Aplicamos el filtro de visibilidad si NO es Administrador ni Supervisor
        if (!$user->hasAnyRole(['Administrador', 'Supervisor'])) {
            $allowedSectors = $user->sectors->pluck('id');
            $baseQuery->whereIn('sector_id', $allowedSectors);
        }

        // 3. Obtenemos todos los activos para calcular KPIs globales sin distorsión por filtro de búsqueda
        $allActive = (clone $baseQuery)->get();
        $pendientes = $allActive->where('status', 'Pendiente')->count();
        $enCamino = $allActive->where('status', 'En Camino')->count();
        $enElLugar = $allActive->where('status', 'En el Lugar')->count();

        // 4. Aplicamos filtros rápidos de consola (estado, sector, búsqueda libre por Folio, calle o metadatos/PPU)
        if ($this->filterStatus) {
            $baseQuery->where('status', $this->filterStatus);
        }

        if ($this->filterSector) {
            $baseQuery->where('sector_id', $this->filterSector);
        }

        if (trim($this->search) !== '') {
            $term = trim(ltrim($this->search, '#'));
            $baseQuery->where(function ($q) use ($term) {
                $q->where('id', $term)
                  ->orWhere('exact_address', 'like', "%{$term}%")
                  ->orWhere('caller_name', 'like', "%{$term}%")
                  ->orWhere('caller_phone', 'like', "%{$term}%")
                  ->orWhere('metadata', 'like', "%{$term}%");
            });
        }

        $procedures = $baseQuery->get();

        // 5. Cálculo de SLA Promedio del Día (Despacho y Arribo en minutos)
        $todayCases = Procedure::whereDate('created_at', today())
            ->when(!$user->hasAnyRole(['Administrador', 'Supervisor']), function ($q) use ($user) {
                $q->whereIn('sector_id', $user->sectors->pluck('id'));
            })
            ->get();

        $dispatchedToday = $todayCases->filter(fn($p) => !is_null($p->dispatch_minutes));
        $arrivedToday = $todayCases->filter(fn($p) => !is_null($p->arrived_at) && !is_null($p->arrival_minutes));

        $avgDispatchMinutes = $dispatchedToday->count() > 0
            ? round($dispatchedToday->avg('dispatch_minutes'), 1)
            : null;

        $avgArrivalMinutes = $arrivedToday->count() > 0
            ? round($arrivedToday->avg('arrival_minutes'), 1)
            : null;

        // 6. Consultar si existe un Evento Masivo / Contingencia activa
        $activeEvent = \App\Models\OperationalEvent::withCount([
            'reports',
            'reports as pending_reports_count' => fn($q) => $q->where('status', 'Pendiente'),
            'reports as in_progress_reports_count' => fn($q) => $q->where('status', 'En Atención'),
        ])->where('is_active', true)->latest()->first();

        // 7. Monitor de Estado de Flota (QAP)
        $fleetDevices = \App\Models\Device::with(['activePatrolAssignment.sector', 'activePatrolAssignment.crew'])
            ->withCount('activeProcedures')
            ->orderBy('name')
            ->get();

        $sectors = $user->hasAnyRole(['Administrador', 'Supervisor'])
            ? Sector::orderBy('name')->get()
            : $user->sectors()->orderBy('name')->get();

        $activeShift = \App\Models\OperatorShift::with('openedBy')
            ->where('is_active', true)
            ->latest('started_at')
            ->first();

        $lastClosedShift = \App\Models\OperatorShift::with(['openedBy', 'closedBy'])
            ->where('is_active', false)
            ->latest('closed_at')
            ->first();

        return view('livewire.active-procedures', [
            'procedures' => $procedures,
            'pendientes' => $pendientes,
            'enCamino' => $enCamino,
            'enElLugar' => $enElLugar,
            'avgDispatchMinutes' => $avgDispatchMinutes,
            'avgArrivalMinutes' => $avgArrivalMinutes,
            'activeEvent' => $activeEvent,
            'fleetDevices' => $fleetDevices,
            'sectors' => $sectors,
            'activeShift' => $activeShift,
            'lastClosedShift' => $lastClosedShift,
        ]);
    }
}