<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Procedure;
use App\Models\Sector;
use App\Models\ProcedureType;
use Carbon\Carbon;
use App\Exports\ProceduresExport;
use Maatwebsite\Excel\Facades\Excel;

class ReportsOverview extends Component
{
    use WithPagination;

    // Variables de los filtros
    public $startDate;
    public $endDate;
    public $sector_id = '';
    public $procedure_type_id = '';
    public $status_filter = '';
    public $device_id = '';

    // Catálogos para los selects
    public $sectors;
    public $procedureTypes;
    public $devices;

    public function mount()
    {
        // Por defecto, mostramos los casos de los últimos 30 días
        $this->startDate = Carbon::now()->subDays(30)->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
        
        $this->sectors = Sector::orderBy('name')->get();
        $this->procedureTypes = ProcedureType::orderBy('name')->get();
        $this->devices = \App\Models\Device::orderBy('name')->get();
    }

    // Si el usuario cambia un filtro, reiniciamos a la página 1 de la tabla
    public function updating($field)
    {
        $this->resetPage();
    }

    // ¡NUEVO! Esta función mágica se ejecuta AUTOMÁTICAMENTE después de que cambia cualquier filtro
    public function updated()
    {
        $this->updateMap();
    }

    public function exportToExcel()
    {
        $fileName = 'Reporte_Procedimientos_' . now()->format('Y-m-d_H-i') . '.xlsx';

        // Le pasamos los filtros actuales al exportador
        return Excel::download(new ProceduresExport(
            $this->startDate,
            $this->endDate,
            $this->sector_id,
            $this->procedure_type_id
        ), $fileName);
    }

    // Función del Mapa de Calor (Ahora con todos los filtros)
    public function updateMap()
    {
        $query = Procedure::whereNotNull('latitude')->whereNotNull('longitude');

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('created_at', [$this->startDate . ' 00:00:00', $this->endDate . ' 23:59:59']);
        }
        
        if ($this->procedure_type_id) {
            $query->where('procedure_type_id', $this->procedure_type_id);
        }

        if ($this->sector_id) {
            $query->where('sector_id', $this->sector_id);
        }

        if ($this->status_filter) {
            $query->where('status', $this->status_filter);
        }

        if ($this->device_id) {
            $query->where('device_id', $this->device_id);
        }

        // Extraemos las coordenadas
        $coordinates = $query->get(['latitude', 'longitude'])->map(function($item) {
            return [
                'lat' => (float) $item->latitude,
                'lng' => (float) $item->longitude
            ];
        })->toArray();

        // Enviamos el evento a Deck.gl
        $this->dispatch('update-heatmap', data: $coordinates);
    }

    public function render()
    {
        // 1. Iniciamos la consulta base
        $query = Procedure::with(['sector', 'procedureType', 'device', 'user'])
            ->whereDate('procedures.created_at', '>=', $this->startDate)
            ->whereDate('procedures.created_at', '<=', $this->endDate)
            ->when($this->sector_id, fn($q) => $q->where('procedures.sector_id', $this->sector_id))
            ->when($this->procedure_type_id, fn($q) => $q->where('procedures.procedure_type_id', $this->procedure_type_id))
            ->when($this->status_filter, fn($q) => $q->where('procedures.status', $this->status_filter))
            ->when($this->device_id, fn($q) => $q->where('procedures.device_id', $this->device_id));
        
        // 2. Calcular KPIs y SLA Promedio
        $totalCases = $query->count();
        $closedCases = (clone $query)->where('procedures.status', 'Cerrado')->count();

        $casesForSla = (clone $query)->select(['procedures.id', 'procedures.created_at', 'procedures.dispatched_at', 'procedures.arrived_at', 'procedures.mobile_arrived_at', 'procedures.status'])->get();
        $dispatchedList = $casesForSla->filter(fn($p) => !is_null($p->dispatched_at));
        $arrivedList = $casesForSla->filter(fn($p) => !is_null($p->arrived_at));

        $avgDispatchSla = $dispatchedList->count() > 0
            ? round($dispatchedList->avg(fn($p) => $p->created_at->diffInMinutes($p->dispatched_at)), 1)
            : null;

        $avgArrivalSla = $arrivedList->count() > 0
            ? round($arrivedList->avg(fn($p) => ($p->dispatched_at ?? $p->created_at)->diffInMinutes($p->arrived_at)), 1)
            : null;

        // 3. Gráfico 1: Agrupamos por Sector
        $chartData = (clone $query)
            ->join('sectors', 'procedures.sector_id', '=', 'sectors.id')
            ->selectRaw('sectors.name as sector_name, count(procedures.id) as total')
            ->groupBy('sectors.name')
            ->get();

        $chartLabels = $chartData->pluck('sector_name')->toJson();
        $chartValues = $chartData->pluck('total')->toJson();

        // 4. Gráfico 2: Top Tipos de Incidente
        $typeData = (clone $query)
            ->join('procedure_types', 'procedures.procedure_type_id', '=', 'procedure_types.id')
            ->selectRaw('procedure_types.name as type_name, count(procedures.id) as total')
            ->groupBy('procedure_types.name')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        $typeLabels = $typeData->pluck('type_name')->toJson();
        $typeValues = $typeData->pluck('total')->toJson();

        // 5. Gráfico 3: Distribución Horaria (00:00 a 23:00 hrs)
        $hoursCount = array_fill(0, 24, 0);
        foreach ($casesForSla as $c) {
            $hr = (int) $c->created_at->format('G');
            $hoursCount[$hr]++;
        }
        $hourlyLabels = collect(range(0, 23))->map(fn($h) => str_pad($h, 2, '0', STR_PAD_LEFT) . ':00')->toJson();
        $hourlyValues = collect(array_values($hoursCount))->toJson();

        // 6. Traer los resultados paginados para la tabla
        $procedures = $query->orderBy('procedures.created_at', 'desc')->paginate(15);

        return view('livewire.reports-overview', [
            'procedures' => $procedures,
            'totalCases' => $totalCases,
            'closedCases' => $closedCases,
            'avgDispatchSla' => $avgDispatchSla,
            'avgArrivalSla' => $avgArrivalSla,
            'chartLabels' => $chartLabels,
            'chartValues' => $chartValues,
            'typeLabels' => $typeLabels,
            'typeValues' => $typeValues,
            'hourlyLabels' => $hourlyLabels,
            'hourlyValues' => $hourlyValues,
        ])->layout('layouts.app');
    }
}