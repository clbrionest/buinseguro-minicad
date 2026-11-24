<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Device;
use App\Models\EventReport;
use App\Models\Evidence;
use App\Models\Landmark;
use App\Models\PatrolAssignment;
use App\Models\PreventivePatrol;
use App\Models\PreventivePatrolCheckpoint;
use App\Models\Procedure;
use App\Models\ProcedureLog;
use App\Models\SystemCatalogItem;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class FieldInspectorPanel extends Component
{
    use WithFileUploads;

    public $selected_device_id = '';

    public $active_procedure_for_note = null;

    public $quick_action_type = 'Novedad Terreno';

    public $quick_external_agency = '';

    public $quick_close_after_derivation = false;

    public $quick_closure_resolution = '';

    public $quick_note = '';

    public $quick_photo = null;

    // Formulario para agregar puntos/hitos adicionales al patrullaje preventivo en curso
    public $showAddPatrolPointForm = false;

    public $new_patrol_landmark_id = '';

    public $new_patrol_point_name = '';

    public $new_patrol_point_address = '';

    public $validate_new_point_immediately = true;

    public function mount()
    {
        $user = Auth::user();

        if ($user && $user->isInspectorOnly()) {
            $assignedIds = $user->assignedDeviceIds();
            $this->selected_device_id = $assignedIds[0] ?? '';

            return;
        }

        // Buscar si el usuario actual forma parte de una patrulla activa
        $assignment = PatrolAssignment::where('is_active', true)
            ->whereHas('crew', fn ($q) => $q->where('users.id', $user->id))
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
        if (! $this->selected_device_id) {
            return;
        }

        $user = Auth::user();
        if ($user && $user->isInspectorOnly()) {
            if (! in_array((int) $this->selected_device_id, $user->assignedDeviceIds(), true)) {
                abort(403, 'Solo puedes cambiar el estado del móvil asignado a tu turno.');
            }
        }

        $catalogStatuses = SystemCatalogItem::namesFor('device_statuses', [
            'Disponible',
            'En Colación',
            'Punto Fijo',
            'En Mantención',
        ]);

        $normalizedInput = trim(str_replace(' (QAP)', '', (string) $status));
        $normalizedCatalog = array_map(
            fn ($item) => trim(str_replace(' (QAP)', '', (string) $item)),
            $catalogStatuses
        );

        $allowedNormalized = array_unique(array_merge($normalizedCatalog, [
            'Disponible',
            'En Colación',
            'Punto Fijo',
            'En Mantención',
        ]));

        if (! in_array($normalizedInput, $allowedNormalized, true)) {
            return;
        }

        Device::where('id', $this->selected_device_id)->update([
            'operational_status' => $normalizedInput,
        ]);

        session()->flash('field_message', 'Estado del móvil actualizado a: '.$normalizedInput);
    }

    #[On('preventive-patrol-created')]
    public function handlePreventivePatrolCreated(): void
    {
        // Refresca la vista cuando se inicia un patrullaje preventivo
    }

    public function updateDeviceGps($lat, $lng, $speed = null, $silent = false)
    {
        if (! $this->selected_device_id || ! $lat || ! $lng) {
            return;
        }

        $device = Device::find($this->selected_device_id);
        if ($device) {
            $device->recordGpsPoint($lat, $lng, $speed);
        }

        if (! $silent) {
            session()->flash('field_message', 'Ubicación GPS del móvil actualizada en el Mapa Operativo.');
        }
    }

    public function toggleAddPatrolPointForm(): void
    {
        $this->showAddPatrolPointForm = ! $this->showAddPatrolPointForm;
        $this->reset(['new_patrol_landmark_id', 'new_patrol_point_name', 'new_patrol_point_address']);
        $this->validate_new_point_immediately = true;
    }

    public function updatedNewPatrolLandmarkId($value): void
    {
        if ($value) {
            $landmark = Landmark::find($value);
            if ($landmark) {
                $this->new_patrol_point_name = $landmark->name;
                $this->new_patrol_point_address = $landmark->address;
            }
        } else {
            $this->reset(['new_patrol_point_name', 'new_patrol_point_address']);
        }
    }

    public function addAdditionalPatrolCheckpoint($lat = null, $lng = null): void
    {
        if (! $this->selected_device_id) {
            return;
        }

        $activePatrol = PreventivePatrol::where('device_id', $this->selected_device_id)
            ->where('status', 'En Curso')
            ->latest('started_at')
            ->first();

        if (! $activePatrol) {
            return;
        }

        $landmark = $this->new_patrol_landmark_id ? Landmark::find($this->new_patrol_landmark_id) : null;
        if ($landmark && empty(trim((string) $this->new_patrol_point_name))) {
            $this->new_patrol_point_name = $landmark->name;
        }

        $this->validate([
            'new_patrol_point_name' => 'required|string|max:120',
            'new_patrol_point_address' => 'nullable|string|max:200',
        ], [
            'new_patrol_point_name.required' => 'Indique el nombre del punto o seleccione un hito.',
        ]);

        $device = Device::find($this->selected_device_id);
        $pointLat = $landmark?->latitude ?? ($lat ?: ($device?->last_lat ?: -33.7320));
        $pointLng = $landmark?->longitude ?? ($lng ?: ($device?->last_lng ?: -70.7420));
        $nextOrder = ((int) $activePatrol->checkpoints()->max('order')) + 1;

        $checkpoint = PreventivePatrolCheckpoint::create([
            'preventive_patrol_id' => $activePatrol->id,
            'landmark_id' => $landmark?->id,
            'name' => trim((string) $this->new_patrol_point_name),
            'address' => trim((string) $this->new_patrol_point_address) ?: ($landmark?->address ?: 'Punto adicional en terreno'),
            'latitude' => (float) $pointLat,
            'longitude' => (float) $pointLng,
            'order' => $nextOrder,
            'status' => 'Pendiente',
        ]);

        if ($this->validate_new_point_immediately) {
            $valLat = is_numeric($lat) ? (float) $lat : (float) $pointLat;
            $valLng = is_numeric($lng) ? (float) $lng : (float) $pointLng;

            $checkpoint->validateCheckpoint(
                lat: $valLat,
                lng: $valLng,
                userId: Auth::id(),
                notes: 'Punto adicional incorporado y validado en terreno por inspector'
            );

            if ($device && is_numeric($lat) && is_numeric($lng)) {
                $device->recordGpsPoint($valLat, $valLng);
            } else {
                $activePatrol->recordGpsPoint($valLat, $valLng);
            }
        }

        AuditLog::record(
            'preventive_patrol.checkpoint_added',
            'Patrullaje Preventivo',
            "Inspector agregó punto adicional '{$checkpoint->name}' al patrullaje {$activePatrol->folio}.",
            $activePatrol,
            [
                'checkpoint_id' => $checkpoint->id,
                'name' => $checkpoint->name,
                'validated_immediately' => (bool) $this->validate_new_point_immediately,
            ]
        );

        $this->showAddPatrolPointForm = false;
        $this->reset(['new_patrol_landmark_id', 'new_patrol_point_name', 'new_patrol_point_address']);
        $this->validate_new_point_immediately = true;

        session()->flash('field_message', "Punto adicional '{$checkpoint->name}' agregado al patrullaje {$activePatrol->folio}.");
    }

    public function validatePatrolCheckpoint($checkpointId, $lat = null, $lng = null)
    {
        $checkpoint = PreventivePatrolCheckpoint::findOrFail($checkpointId);

        $checkpoint->validateCheckpoint(
            lat: $lat ? (float) $lat : null,
            lng: $lng ? (float) $lng : null,
            userId: Auth::id()
        );

        if ($lat && $lng) {
            $device = Device::find($this->selected_device_id);
            if ($device) {
                $device->recordGpsPoint($lat, $lng);
            } else {
                $checkpoint->preventivePatrol->recordGpsPoint($lat, $lng);
            }
        }

        session()->flash('field_message', "Hito '{$checkpoint->name}' validado con éxito.");
    }

    public function confirmArrivalFromField($procedureId, $lat = null, $lng = null)
    {
        $procedure = Procedure::findOrFail($procedureId);
        $user = Auth::user();
        if ($user && ! $user->canAccessProcedure($procedure)) {
            abort(403, 'No tienes permisos para operar un procedimiento no asignado a tu móvil.');
        }

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
            $device = Device::find($procedure->device_id);
            if ($device) {
                $device->recordGpsPoint($lat, $lng);
            }
        }

        $distanceText = ! is_null($distance)
            ? " (Distancia georreferenciada al punto: {$distance} m)."
            : ($lat && $lng ? " (GPS: {$lat}, {$lng})." : ' (Sin señal GPS disponible).');

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
        $this->reset([
            'quick_note',
            'quick_photo',
            'quick_external_agency',
            'quick_close_after_derivation',
            'quick_closure_resolution',
        ]);
        $this->quick_action_type = 'Novedad Terreno';
    }

    public function updatedQuickActionType(): void
    {
        $this->resetValidation(['quick_external_agency', 'quick_closure_resolution', 'quick_note']);
        if ($this->quick_action_type !== 'Derivación Externa') {
            $this->reset(['quick_external_agency', 'quick_close_after_derivation']);
        }
        if ($this->quick_action_type !== 'Cierre de Procedimiento') {
            $this->reset(['quick_closure_resolution']);
        }
    }

    public function saveQuickNote($procedureId)
    {
        $procedure = Procedure::findOrFail($procedureId);
        $user = Auth::user();
        if ($user && ! $user->canAccessProcedure($procedure)) {
            abort(403, 'No tienes permisos para operar un procedimiento no asignado a tu móvil.');
        }

        $isDerivation = $this->quick_action_type === 'Derivación Externa';
        $isClosure = $this->quick_action_type === 'Cierre de Procedimiento';

        $rules = [
            'quick_action_type' => 'required|in:Novedad Terreno,Derivación Externa,Cierre de Procedimiento',
            'quick_photo' => 'nullable|image|max:5120',
        ];
        $messages = [];

        if ($isDerivation) {
            $rules['quick_external_agency'] = 'required|string|max:255';
            $messages['quick_external_agency.required'] = 'Seleccione el organismo externo.';
            $rules['quick_note'] = 'nullable|string|max:2000';
        } elseif ($isClosure) {
            $rules['quick_closure_resolution'] = 'required|string|max:255';
            $messages['quick_closure_resolution.required'] = 'Seleccione la resolución de cierre.';
            $rules['quick_note'] = 'nullable|string|max:2000';
        } else {
            $rules['quick_note'] = 'required_without:quick_photo|nullable|string|max:2000';
            $messages['quick_note.required_without'] = 'Escriba una novedad o adjunte una fotografía.';
        }

        $this->validate($rules, $messages);

        if ($this->quick_photo) {
            $path = $this->quick_photo->store('evidences', 'public');
            Evidence::create([
                'procedure_id' => $procedure->id,
                'user_id' => Auth::id(),
                'file_path' => $path,
            ]);
        }

        $noteText = trim((string) $this->quick_note);
        $logActionType = $this->quick_action_type;
        $oldStatus = $procedure->status;
        $nextStatus = $oldStatus;
        $meta = is_array($procedure->metadata) ? $procedure->metadata : [];
        $updates = [];

        if ($isDerivation) {
            $agency = trim((string) $this->quick_external_agency);
            $logActionType = 'Coordinación Externa (Terreno)';
            $nextStatus = $this->quick_close_after_derivation ? 'Cerrado' : 'Derivado';
            $header = "Derivado a: [{$agency}]".($this->quick_close_after_derivation ? ' — Cierre inmediato tras derivación' : '');
            $finalDescription = $noteText !== '' ? "{$header}. {$noteText}" : "{$header}.";

            $meta['_derived_to'] = $agency;
            if ($this->quick_close_after_derivation) {
                $meta['_closure_resolution'] = "Derivado a {$agency}";
            }
            $updates['metadata'] = $meta;
        } elseif ($isClosure) {
            $res = trim((string) $this->quick_closure_resolution);
            $nextStatus = 'Cerrado';
            $header = "Resolución de Cierre: [{$res}]";
            $finalDescription = $noteText !== '' ? "{$header}. {$noteText}" : "{$header}.";

            $meta['_closure_resolution'] = $res;
            $updates['metadata'] = $meta;
        } else {
            $finalDescription = $noteText !== '' ? $noteText : 'Evidencia fotográfica enviada desde Modo Terreno.';
        }

        ProcedureLog::create([
            'procedure_id' => $procedure->id,
            'user_id' => Auth::id(),
            'action_type' => $logActionType,
            'description' => $finalDescription,
        ]);

        if ($nextStatus !== $oldStatus) {
            $updates['status'] = $nextStatus;
            if ($nextStatus === 'Cerrado' && ! $procedure->closed_at) {
                $updates['closed_at'] = now();
            }
        }

        if (! empty($updates)) {
            $procedure->update($updates);
            if ($nextStatus !== $oldStatus) {
                AuditLog::record(
                    'procedure.status_changed',
                    'Procedimientos',
                    "Cambio de estado desde Terreno en Procedimiento #{$procedure->id}: de \"{$oldStatus}\" a \"{$nextStatus}\".",
                    $procedure,
                    [
                        'old_status' => $oldStatus,
                        'new_status' => $nextStatus,
                        'action_type' => $logActionType,
                    ]
                );
            }
        }

        $this->reset([
            'quick_note',
            'quick_photo',
            'quick_external_agency',
            'quick_close_after_derivation',
            'quick_closure_resolution',
            'active_procedure_for_note',
        ]);
        $this->quick_action_type = 'Novedad Terreno';

        $feedback = match ($nextStatus) {
            'Cerrado' => "Procedimiento #{$procedure->id} cerrado exitosamente desde Terreno.",
            'Derivado' => "Derivación registrada para el Caso #{$procedure->id}.",
            default => "Novedad registrada exitosamente en el Caso #{$procedure->id}.",
        };
        session()->flash('field_message', $feedback);
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
        $user = Auth::user();
        $devicesQuery = Device::with(['activePatrolAssignment.sector', 'activePatrolAssignment.crew'])
            ->withCount('activeProcedures')
            ->orderBy('name');

        if ($user && $user->isInspectorOnly()) {
            $assignedIds = $user->assignedDeviceIds();
            $devicesQuery->whereIn('id', ! empty($assignedIds) ? $assignedIds : [-1]);
        }

        $devices = $devicesQuery->get();

        $currentDevice = $this->selected_device_id
            ? $devices->firstWhere('id', (int) $this->selected_device_id)
            : null;

        $assignedProcedures = collect();
        $assignedEventReports = collect();
        $activePreventivePatrol = null;

        if ($currentDevice) {
            $assignedProcedures = Procedure::with(['sector', 'procedureType', 'origin'])
                ->where('device_id', $currentDevice->id)
                ->whereIn('status', ['En Camino', 'En el Lugar', 'Derivado'])
                ->orderBy('created_at', 'desc')
                ->get();

            $assignedEventReports = EventReport::with(['event', 'sector'])
                ->where('device_id', $currentDevice->id)
                ->where('status', 'En Atención')
                ->whereHas('event', fn ($q) => $q->where('is_active', true))
                ->orderBy('created_at', 'desc')
                ->get();

            $activePreventivePatrol = PreventivePatrol::with(['checkpoints'])
                ->where('device_id', $currentDevice->id)
                ->where('status', 'En Curso')
                ->latest('started_at')
                ->first();
        }

        $catalogs = ShowProcedure::splitResolutionsCatalog();

        return view('livewire.field-inspector-panel', [
            'devices' => $devices,
            'currentDevice' => $currentDevice,
            'assignedProcedures' => $assignedProcedures,
            'assignedEventReports' => $assignedEventReports,
            'activePreventivePatrol' => $activePreventivePatrol,
            'landmarks' => Landmark::orderBy('name')->get(),
            'externalAgencies' => $catalogs['external_agencies'],
            'closureResolutions' => $catalogs['closure_resolutions'],
        ])->layout('layouts.app');
    }
}
