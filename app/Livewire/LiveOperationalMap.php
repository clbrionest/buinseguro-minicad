<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Procedure;

class LiveOperationalMap extends Component
{
    public $locations = [];
    public $vehicles = [];

    public function mount()
    {
        $this->loadActiveProcedures();
    }

    // Esta función será llamada cada 10 segundos por el navegador
    public function loadActiveProcedures()
    {
        $user = auth()->user();

        // Solo traemos casos que no estén cerrados ni cancelados, y que tengan GPS
        $query = Procedure::with(['procedureType', 'device'])
            ->whereIn('status', ['Pendiente', 'En Camino', 'En el Lugar', 'Derivado'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        // Aplicamos filtro de visibilidad por sector si no es Administrador ni Supervisor
        if ($user && !$user->hasAnyRole(['Administrador', 'Supervisor'])) {
            $allowedSectors = $user->sectors->pluck('id');
            $query->whereIn('sector_id', $allowedSectors);
        }

        $procedures = $query->get();

        $this->locations = $procedures->map(function($p) {
            return [
                'id' => $p->id,
                'lat' => (float) $p->latitude,
                'lng' => (float) $p->longitude,
                'type' => $p->procedureType->name ?? 'Procedimiento',
                'status' => $p->status,
                'device' => $p->device ? $p->device->name : 'Sin asignar',
                'address' => $p->exact_address
            ];
        })->toArray();

        // Cargar móviles que han reportado coordenadas GPS en terreno
        $devices = \App\Models\Device::with(['activePatrolAssignment.sector', 'activePatrolAssignment.crew'])
            ->withCount('activeProcedures')
            ->whereNotNull('last_lat')
            ->whereNotNull('last_lng')
            ->get();

        $this->vehicles = $devices->map(function($d) {
            return [
                'id' => $d->id,
                'name' => $d->name,
                'lat' => (float) $d->last_lat,
                'lng' => (float) $d->last_lng,
                'status' => $d->effective_status,
                'sector' => $d->activePatrolAssignment?->sector?->name ?? 'Sin sector',
                'crew' => $d->activePatrolAssignment?->crew?->pluck('name')->join(', ') ?: 'Sin tripulación',
                'updated' => $d->last_location_at ? $d->last_location_at->format('H:i:s') : 'N/A',
            ];
        })->toArray();

        // Disparamos un evento a Javascript con los datos actualizados
        $this->dispatch('map-updated', data: $this->locations, vehicles: $this->vehicles);
    }

    public function render()
    {
        return view('livewire.live-operational-map')->layout('layouts.app');
    }
}