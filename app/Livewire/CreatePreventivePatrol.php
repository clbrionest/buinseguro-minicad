<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Device;
use App\Models\Landmark;
use App\Models\PatrolAssignment;
use App\Models\PreventivePatrol;
use App\Models\PreventivePatrolCheckpoint;
use App\Models\Sector;
use App\Models\SystemCatalogItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class CreatePreventivePatrol extends Component
{
    public $showButton = false;

    public $isOpen = false;

    // Campos del formulario
    public $device_id = '';

    public $sector_id = '';

    public $patrol_type = 'Ronda Preventiva General';

    public $started_at = '';

    public $start_odometer = '';

    public $mission_objective = '';

    // Hitos seleccionados
    public $selected_landmarks = [];

    // Hitos adicionales personalizados
    public $custom_checkpoints = [];

    public $new_checkpoint_name = '';

    public $new_checkpoint_address = '';

    public $new_checkpoint_lat = '';

    public $new_checkpoint_lng = '';

    // Colecciones para los selects
    public $devices = [];

    public $sectors = [];

    public $landmarks = [];

    public $patrolTypes = [];

    public function mount($showButton = false)
    {
        $this->showButton = $showButton;
        $this->started_at = Carbon::now()->format('Y-m-d\TH:i');
    }

    public function getPatrolTypesList(): array
    {
        return SystemCatalogItem::namesFor('preventive_patrol_types', [
            'Ronda Preventiva General',
            'Ronda Escolar / Colegios',
            'Ronda Bancaria y Comercio',
            'Ronda Nocturna Residencial',
            'Punto Fijo Preventivo',
            'Fiscalización y Control',
        ]);
    }

    #[On('open-create-patrol-modal')]
    public function openModal($deviceId = null, $sectorId = null)
    {
        $this->resetValidation();
        $this->resetForm();

        $user = Auth::user();
        if ($user && ! $user->hasAnyRole(['Administrador', 'Supervisor'])) {
            $this->sectors = $user->sectors()->orderBy('name')->get();
        } else {
            $this->sectors = Sector::orderBy('name')->get();
        }

        $this->devices = Device::with(['activePatrolAssignment.sector', 'activePatrolAssignment.crew'])
            ->orderBy('name')
            ->get();

        $this->landmarks = Landmark::orderBy('name')->get();
        $this->patrolTypes = $this->getPatrolTypesList();

        if ($deviceId) {
            $this->device_id = $deviceId;
            $dev = $this->devices->firstWhere('id', $deviceId);
            if ($dev && $dev->activePatrolAssignment && ! $sectorId) {
                $this->sector_id = $dev->activePatrolAssignment->sector_id;
            }
        }

        if ($sectorId) {
            $this->sector_id = $sectorId;
        }

        $this->started_at = Carbon::now()->format('Y-m-d\TH:i');
        $this->isOpen = true;
    }

    public function closeModal()
    {
        $this->isOpen = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->reset([
            'device_id',
            'sector_id',
            'start_odometer',
            'mission_objective',
            'selected_landmarks',
            'custom_checkpoints',
            'new_checkpoint_name',
            'new_checkpoint_address',
            'new_checkpoint_lat',
            'new_checkpoint_lng',
        ]);
        $this->patrol_type = 'Ronda Preventiva General';
        $this->started_at = Carbon::now()->format('Y-m-d\TH:i');
    }

    public function addCustomCheckpoint()
    {
        $this->validate([
            'new_checkpoint_name' => 'required|string|max:120',
            'new_checkpoint_address' => 'nullable|string|max:200',
            'new_checkpoint_lat' => 'nullable|numeric|between:-90,90',
            'new_checkpoint_lng' => 'nullable|numeric|between:-180,180',
        ], [
            'new_checkpoint_name.required' => 'Indique el nombre del hito o punto de control.',
        ]);

        $this->custom_checkpoints[] = [
            'name' => trim($this->new_checkpoint_name),
            'address' => trim($this->new_checkpoint_address) ?: 'Comuna de Buin',
            'latitude' => $this->new_checkpoint_lat ?: -33.7320,
            'longitude' => $this->new_checkpoint_lng ?: -70.7420,
        ];

        $this->reset([
            'new_checkpoint_name',
            'new_checkpoint_address',
            'new_checkpoint_lat',
            'new_checkpoint_lng',
        ]);
    }

    public function removeCustomCheckpoint($index)
    {
        unset($this->custom_checkpoints[$index]);
        $this->custom_checkpoints = array_values($this->custom_checkpoints);
    }

    public function selectAllLandmarks()
    {
        $this->selected_landmarks = collect($this->landmarks)->pluck('id')->map(fn ($id) => (string) $id)->toArray();
    }

    public function clearSelectedLandmarks()
    {
        $this->selected_landmarks = [];
    }

    public function save()
    {
        $this->validate([
            'device_id' => 'required|exists:devices,id',
            'sector_id' => 'required|exists:sectors,id',
            'patrol_type' => 'required|string|max:120',
            'started_at' => 'required|date',
            'start_odometer' => 'nullable|integer|min:0',
            'mission_objective' => 'nullable|string|max:1500',
        ], [
            'device_id.required' => 'Debe seleccionar el móvil o vehículo de patrullaje.',
            'sector_id.required' => 'Debe indicar el sector del patrullaje.',
            'patrol_type.required' => 'Seleccione el tipo de patrullaje preventivo.',
            'started_at.required' => 'Indique la fecha y hora de inicio del patrullaje.',
        ]);

        $device = Device::findOrFail($this->device_id);
        $folio = PreventivePatrol::generateNextFolio();

        // Buscar si el móvil tiene un turno de asignación activo en flota
        $activeAssignment = PatrolAssignment::where('device_id', $this->device_id)
            ->where('is_active', true)
            ->latest()
            ->first();

        $initialRoute = null;
        if ($device->last_lat && $device->last_lng) {
            $initialRoute = [
                [
                    'lat' => (float) $device->last_lat,
                    'lng' => (float) $device->last_lng,
                    'recorded_at' => Carbon::parse($this->started_at)->toIso8601String(),
                    'speed' => null,
                ],
            ];
        }

        $patrol = PreventivePatrol::create([
            'folio' => $folio,
            'device_id' => $this->device_id,
            'sector_id' => $this->sector_id,
            'user_id' => Auth::id(),
            'patrol_assignment_id' => $activeAssignment?->id,
            'patrol_type' => $this->patrol_type,
            'status' => 'En Curso',
            'started_at' => Carbon::parse($this->started_at),
            'start_odometer' => $this->start_odometer ?: null,
            'mission_objective' => trim((string) $this->mission_objective) ?: null,
            'gps_route' => $initialRoute,
        ]);

        // Crear los hitos seleccionados desde catálogo
        $order = 1;
        if (! empty($this->selected_landmarks)) {
            $landmarksList = Landmark::whereIn('id', $this->selected_landmarks)->get();
            foreach ($landmarksList as $lm) {
                PreventivePatrolCheckpoint::create([
                    'preventive_patrol_id' => $patrol->id,
                    'landmark_id' => $lm->id,
                    'name' => $lm->name,
                    'address' => $lm->address,
                    'latitude' => $lm->latitude,
                    'longitude' => $lm->longitude,
                    'order' => $order++,
                    'status' => 'Pendiente',
                ]);
            }
        }

        // Crear los hitos adicionales personalizados
        foreach ($this->custom_checkpoints as $cp) {
            PreventivePatrolCheckpoint::create([
                'preventive_patrol_id' => $patrol->id,
                'landmark_id' => null,
                'name' => $cp['name'],
                'address' => $cp['address'],
                'latitude' => $cp['latitude'],
                'longitude' => $cp['longitude'],
                'order' => $order++,
                'status' => 'Pendiente',
            ]);
        }

        AuditLog::record(
            'preventive_patrol.created',
            'Patrullaje Preventivo',
            "Inicio de Patrullaje Preventivo Folio {$patrol->folio} con Móvil {$device->name} en sector {$patrol->sector?->name}.",
            $patrol,
            [
                'device_id' => $patrol->device_id,
                'sector_id' => $patrol->sector_id,
                'checkpoints_count' => $order - 1,
            ]
        );

        $this->closeModal();
        $this->dispatch('preventive-patrol-created', patrolId: $patrol->id);
        session()->flash('message', "Patrullaje Preventivo {$patrol->folio} iniciado exitosamente.");
    }

    public function render()
    {
        return view('livewire.create-preventive-patrol');
    }
}
