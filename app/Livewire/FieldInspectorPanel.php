<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Device;
use App\Models\Procedure;
use App\Models\ProcedureLog;
use App\Models\Evidence;
use App\Models\PatrolAssignment;
use App\Models\EventReport;
use Illuminate\Support\Facades\Auth;

class FieldInspectorPanel extends Component
{
    use WithFileUploads;

    public $selected_device_id = '';
    public $active_procedure_for_note = null;
    public $quick_note = '';
    public $quick_photo = null;

    public function mount()
    {
        $user = Auth::user();

        // Buscar si el usuario actual forma parte de una patrulla activa
        $assignment = PatrolAssignment::where('is_active', true)
            ->whereHas('crew', fn($q) => $q->where('users.id', $user->id))
            ->latest()
            ->first();

        if ($assignment) {
            $this->selected_device_id = $assignment->device_id;
        } else {
            $firstDevice = Device::orderBy('name')->first();
            if ($firstDevice) {
                $this->selected_device_id = $firstDevice->id;
            }
        }
    }

    public function setDeviceStatus($status)
    {
        if (!$this->selected_device_id) {
            return;
        }

        $allowed = \App\Models\SystemCatalogItem::namesFor('device_statuses', [
            'Disponible (QAP)',
            'En Colación',
            'Punto Fijo',
            'En Mantención',
        ]);
        if (!in_array($status, $allowed)) {
            return;
        }

        Device::where('id', $this->selected_device_id)->update([
            'operational_status' => $status,
        ]);

        session()->flash('field_message', "Estado del móvil actualizado a: {$status}");
    }

    public function updateDeviceGps($lat, $lng)
    {
        if (!$this->selected_device_id || !$lat || !$lng) {
            return;
        }

        Device::where('id', $this->selected_device_id)->update([
            'last_lat' => $lat,
            'last_lng' => $lng,
            'last_location_at' => now(),
        ]);

        session()->flash('field_message', 'Ubicación GPS del móvil actualizada en el Mapa Operativo.');
    }

    public function confirmArrivalFromField($procedureId, $lat = null, $lng = null)
    {
        $procedure = Procedure::findOrFail($procedureId);

        $distance = Procedure::calculateDistanceMeters(
            $procedure->latitude,
            $procedure->longitude,
            $lat,
            $lng
        );

        $procedure->update([
            'mobile_arrived_at' => now(),
            'mobile_arrival_lat' => $lat ?: null,
            'mobile_arrival_lng' => $lng ?: null,
            'mobile_arrival_distance' => $distance,
        ]);

        if ($procedure->device_id && $lat && $lng) {
            Device::where('id', $procedure->device_id)->update([
                'last_lat' => $lat,
                'last_lng' => $lng,
                'last_location_at' => now(),
            ]);
        }

        $distanceText = !is_null($distance)
            ? " (Distancia georreferenciada al punto: {$distance} m)."
            : ($lat && $lng ? " (GPS: {$lat}, {$lng})." : " (Sin señal GPS disponible).");

        ProcedureLog::create([
            'procedure_id' => $procedure->id,
            'user_id' => Auth::id(),
            'action_type' => 'Llegada Móvil (GPS)',
            'description' => "El móvil reporta llegada al lugar desde Panel Terreno{$distanceText} Pendiente confirmación de operadora en Central.",
            'latitude' => $lat ?: null,
            'longitude' => $lng ?: null,
        ]);

        session()->flash('field_message', "Llegada GPS reportada para el Caso #{$procedure->id}. Central fue notificada.");
    }

    public function openQuickNote($procedureId)
    {
        $this->active_procedure_for_note = ($this->active_procedure_for_note === $procedureId) ? null : $procedureId;
        $this->reset(['quick_note', 'quick_photo']);
    }

    public function saveQuickNote($procedureId)
    {
        $this->validate([
            'quick_note' => 'required_without:quick_photo',
            'quick_photo' => 'nullable|image|max:5120',
        ]);

        $procedure = Procedure::findOrFail($procedureId);

        if ($this->quick_photo) {
            $path = $this->quick_photo->store('evidences', 'public');
            Evidence::create([
                'procedure_id' => $procedure->id,
                'user_id' => Auth::id(),
                'file_path' => $path,
            ]);
            if (empty($this->quick_note)) {
                $this->quick_note = 'Evidencia fotográfica enviada desde Modo Terreno.';
            }
        }

        ProcedureLog::create([
            'procedure_id' => $procedure->id,
            'user_id' => Auth::id(),
            'action_type' => 'Novedad Terreno',
            'description' => $this->quick_note,
        ]);

        $this->reset(['quick_note', 'quick_photo', 'active_procedure_for_note']);
        session()->flash('field_message', "Novedad registrada exitosamente en el Caso #{$procedure->id}.");
    }

    public function resolveEventReportFromField($reportId)
    {
        $report = EventReport::findOrFail($reportId);
        $report->update([
            'status' => 'Resuelto',
            'resolved_at' => now(),
        ]);

        session()->flash('field_message', "Reporte de contingencia #{$report->id} marcado como Resuelto.");
    }

    public function render()
    {
        $devices = Device::with(['activePatrolAssignment.sector', 'activePatrolAssignment.crew'])
            ->withCount('activeProcedures')
            ->orderBy('name')
            ->get();

        $currentDevice = $this->selected_device_id
            ? $devices->firstWhere('id', (int) $this->selected_device_id)
            : null;

        $assignedProcedures = collect();
        $assignedEventReports = collect();

        if ($currentDevice) {
            $assignedProcedures = Procedure::with(['sector', 'procedureType', 'origin'])
                ->where('device_id', $currentDevice->id)
                ->whereIn('status', ['En Camino', 'En el Lugar', 'Derivado'])
                ->orderBy('created_at', 'desc')
                ->get();

            $assignedEventReports = EventReport::with(['event', 'sector'])
                ->where('device_id', $currentDevice->id)
                ->where('status', 'En Atención')
                ->whereHas('event', fn($q) => $q->where('is_active', true))
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return view('livewire.field-inspector-panel', [
            'devices' => $devices,
            'currentDevice' => $currentDevice,
            'assignedProcedures' => $assignedProcedures,
            'assignedEventReports' => $assignedEventReports,
        ])->layout('layouts.app');
    }
}
