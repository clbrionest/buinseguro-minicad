<?php

namespace App\Livewire;

use App\Models\Device;
use App\Models\Procedure;
use Livewire\Component;

class LiveOperationalMap extends Component
{
    public $locations = [];

    public $vehicles = [];

    public $counts = [
        'pending' => 0,
        'on_way' => 0,
        'on_site' => 0,
        'derived' => 0,
        'vehicles' => 0,
    ];

    public function mount()
    {
        $this->loadActiveProcedures();
    }

    // Esta función será llamada periódicamente por Livewire
    public function loadActiveProcedures()
    {
        $user = auth()->user();

        // Solo traemos casos que no estén cerrados ni cancelados, y que tengan GPS
        $query = Procedure::with(['procedureType', 'device', 'sector'])
            ->whereIn('status', ['Pendiente', 'En Camino', 'En el Lugar', 'Derivado'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        // Aplicamos filtro de visibilidad por sector si no es Administrador ni Supervisor
        if ($user && ! $user->hasAnyRole(['Administrador', 'Supervisor'])) {
            $allowedSectors = $user->sectors->pluck('id');
            $query->whereIn('sector_id', $allowedSectors);
        }

        $procedures = $query->get();

        $this->locations = $procedures->map(function ($p) {
            $sla = $p->sla_traffic_light;

            return [
                'id' => $p->id,
                'lat' => (float) $p->latitude,
                'lng' => (float) $p->longitude,
                'type' => $p->procedureType->name ?? 'Procedimiento General',
                'status' => $p->status,
                'device' => $p->device ? $p->device->name : 'Sin asignar',
                'address' => $p->exact_address ?: 'Sin dirección detallada',
                'sector' => $p->sector?->name ?? 'Comuna General',
                'created_at_time' => $p->created_at ? $p->created_at->format('H:i') : '',
                'sla_label' => $sla['label'] ?? '',
                'sla_color' => $sla['color'] ?? 'gray',
                'is_preventive' => (bool) $p->is_preventive,
                'caller_phone' => $p->caller_phone ?? '',
            ];
        })->toArray();

        // Cargar móviles que han reportado coordenadas GPS en terreno
        $devices = Device::with(['activePatrolAssignment.sector', 'activePatrolAssignment.crew'])
            ->withCount('activeProcedures')
            ->whereNotNull('last_lat')
            ->whereNotNull('last_lng')
            ->get();

        $this->vehicles = $devices->map(function ($d) {
            return [
                'id' => $d->id,
                'name' => $d->name,
                'lat' => (float) $d->last_lat,
                'lng' => (float) $d->last_lng,
                'status' => $d->effective_status,
                'sector' => $d->activePatrolAssignment?->sector?->name ?? 'Sin sector',
                'crew' => $d->activePatrolAssignment?->crew?->pluck('name')->join(', ') ?: 'Sin tripulación',
                'updated' => $d->last_location_at ? $d->last_location_at->format('H:i:s') : 'N/A',
                'active_procedures_count' => $d->active_procedures_count,
            ];
        })->toArray();

        $this->counts = [
            'pending' => collect($this->locations)->where('status', 'Pendiente')->count(),
            'on_way' => collect($this->locations)->where('status', 'En Camino')->count(),
            'on_site' => collect($this->locations)->where('status', 'En el Lugar')->count(),
            'derived' => collect($this->locations)->where('status', 'Derivado')->count(),
            'vehicles' => count($this->vehicles),
        ];

        // Disparamos un evento a Javascript con los datos actualizados
        $this->dispatch('map-updated', data: $this->locations, vehicles: $this->vehicles, counts: $this->counts);
    }

    public function render()
    {
        return view('livewire.live-operational-map')->layout('layouts.app');
    }
}
