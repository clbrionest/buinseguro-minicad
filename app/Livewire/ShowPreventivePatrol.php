<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\PreventivePatrol;
use App\Models\PreventivePatrolCheckpoint;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ShowPreventivePatrol extends Component
{
    public PreventivePatrol $patrol;

    public $completion_notes = '';

    public $end_odometer = '';

    public $showCompleteModal = false;

    public function mount(PreventivePatrol $patrol)
    {
        $this->patrol = $patrol;
        $this->completion_notes = $patrol->completion_notes ?? '';
        $this->end_odometer = $patrol->end_odometer ?? '';
    }

    public function validateCheckpoint($checkpointId, $lat = null, $lng = null, $notes = null)
    {
        $checkpoint = PreventivePatrolCheckpoint::where('preventive_patrol_id', $this->patrol->id)
            ->findOrFail($checkpointId);

        // Si no se pasaron coordenadas explícitas, intentar usar las últimas coordenadas del móvil
        if (is_null($lat) && is_null($lng) && $this->patrol->device?->last_lat) {
            $lat = $this->patrol->device->last_lat;
            $lng = $this->patrol->device->last_lng;
        }

        $checkpoint->validateCheckpoint(
            lat: $lat ? (float) $lat : null,
            lng: $lng ? (float) $lng : null,
            userId: Auth::id(),
            notes: $notes
        );

        if ($lat && $lng) {
            $this->patrol->recordGpsPoint($lat, $lng);
        }

        AuditLog::record(
            'preventive_patrol.checkpoint_validated',
            'Patrullaje Preventivo',
            "Validación de hito '{$checkpoint->name}' en Patrullaje {$this->patrol->folio}.".($checkpoint->validation_distance_meters ? " Distancia: {$checkpoint->validation_distance_meters}m." : ''),
            $this->patrol,
            ['checkpoint_id' => $checkpoint->id, 'distance' => $checkpoint->validation_distance_meters]
        );

        $this->patrol->refresh();
        session()->flash('message', "Hito '{$checkpoint->name}' validado correctamente.");
    }

    public function omitCheckpoint($checkpointId, $notes = null)
    {
        $checkpoint = PreventivePatrolCheckpoint::where('preventive_patrol_id', $this->patrol->id)
            ->findOrFail($checkpointId);

        $checkpoint->omitCheckpoint($notes);

        $this->patrol->refresh();
        session()->flash('message', "Hito '{$checkpoint->name}' marcado como omitido.");
    }

    public function completePatrol()
    {
        $this->validate([
            'end_odometer' => 'nullable|integer|min:'.($this->patrol->start_odometer ?? 0),
            'completion_notes' => 'nullable|string|max:2000',
        ], [
            'end_odometer.min' => 'El odómetro final no puede ser menor al odómetro inicial.',
        ]);

        $this->patrol->update([
            'status' => 'Completado',
            'ended_at' => now(),
            'end_odometer' => $this->end_odometer ?: $this->patrol->end_odometer,
            'completion_notes' => trim((string) $this->completion_notes) ?: null,
        ]);

        AuditLog::record(
            'preventive_patrol.completed',
            'Patrullaje Preventivo',
            "Cierre de Patrullaje Preventivo Folio {$this->patrol->folio}. Duración: {$this->patrol->duration_formatted}.",
            $this->patrol,
            [
                'validated_checkpoints' => $this->patrol->validated_checkpoints_count,
                'total_checkpoints' => $this->patrol->total_checkpoints_count,
            ]
        );

        $this->showCompleteModal = false;
        $this->patrol->refresh();
        session()->flash('message', "Patrullaje {$this->patrol->folio} completado y cerrado exitosamente.");
    }

    public function startProcedure()
    {
        $this->dispatch('openModalWithPatrol', patrolId: $this->patrol->id);
    }

    public function render()
    {
        $this->patrol->load([
            'device',
            'sector',
            'user',
            'patrolAssignment.crew',
            'checkpoints.landmark',
            'checkpoints.validatedBy',
            'procedures.procedureType',
            'procedures.user',
        ]);

        return view('livewire.show-preventive-patrol', [
            'patrol' => $this->patrol,
        ])->layout('layouts.app');
    }
}
