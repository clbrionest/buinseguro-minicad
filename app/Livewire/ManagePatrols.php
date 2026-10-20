<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\PatrolAssignment;
use App\Models\Device;
use App\Models\Sector;
use App\Models\User;
use App\Models\SystemCatalogItem;
use App\Models\AuditLog;
use Carbon\Carbon;

class ManagePatrols extends Component
{
    // Filtros de visualización
    public $filterDate;
    public $filterShift = 'Primer Turno (07:00 a 16:00)';

    // Formulario para nueva patrulla
    public $device_id = '';
    public $sector_id = '';
    public $mission_notes = '';

    // Array dinámico para la tripulación (Flexible)
    public $crewMembers = [];

    public function mount()
    {
        abort_unless(auth()->user()?->hasAnyRole(['Administrador', 'Supervisor']), 403);
        $this->filterDate = Carbon::now()->format('Y-m-d');

        $shifts = $this->getShiftBlocks();
        if (!empty($shifts)) {
            $this->filterShift = $shifts[0];
        }

        $this->addCrewMember();
    }

    public function getShiftBlocks(): array
    {
        return SystemCatalogItem::namesFor('patrol_shift_blocks', [
            'Primer Turno (07:00 a 16:00)',
            'Segundo Turno (15:00 a 00:00)',
            'Tercer Turno (00:00 a 08:00)',
        ]);
    }

    public function getCrewRoles(): array
    {
        return SystemCatalogItem::namesFor('patrol_crew_roles', [
            'Conductor',
            'Acompañante',
            'Jefe de Patrulla',
        ]);
    }

    public function addCrewMember()
    {
        $roles = $this->getCrewRoles();
        $defaultRole = count($this->crewMembers) === 0 ? ($roles[0] ?? 'Conductor') : ($roles[1] ?? 'Acompañante');
        $this->crewMembers[] = ['user_id' => '', 'role' => $defaultRole, 'time_exception' => ''];
    }

    public function removeCrewMember($index)
    {
        unset($this->crewMembers[$index]);
        $this->crewMembers = array_values($this->crewMembers);
    }

    public function savePatrol()
    {
        abort_unless(auth()->user()?->hasAnyRole(['Administrador', 'Supervisor']), 403);

        $this->validate([
            'filterDate' => 'required|date',
            'filterShift' => 'required|string|max:150',
            'device_id' => 'required|exists:devices,id',
            'sector_id' => 'nullable|exists:sectors,id',
            'mission_notes' => 'nullable|string|max:1000',
            'crewMembers' => 'required|array|min:1',
            'crewMembers.*.user_id' => 'required|exists:users,id|distinct',
            'crewMembers.*.role' => 'required|string|max:80',
            'crewMembers.*.time_exception' => 'nullable|string|max:120',
        ], [
            'device_id.required' => 'Seleccione el vehículo o móvil.',
            'crewMembers.*.user_id.required' => 'Seleccione al funcionario de la tripulación.',
            'crewMembers.*.user_id.distinct' => 'Un mismo funcionario no puede repetirse dos veces en el mismo móvil.',
            'crewMembers.*.role.required' => 'Indique el rol del funcionario en la patrulla.',
        ]);

        // 0. Desactivamos cualquier turno anterior que haya quedado activo para este mismo móvil
        PatrolAssignment::where('device_id', $this->device_id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        // 1. Creamos la asignación del Móvil
        $patrol = PatrolAssignment::create([
            'date' => $this->filterDate,
            'shift_name' => $this->filterShift,
            'device_id' => $this->device_id,
            'sector_id' => $this->sector_id ?: null,
            'mission_notes' => $this->mission_notes,
            'is_active' => true,
        ]);

        // 2. Asociamos a la tripulación con sus roles y excepciones usando sync()
        $crewData = [];
        foreach ($this->crewMembers as $member) {
            if (!empty($member['user_id'])) {
                $crewData[$member['user_id']] = [
                    'role' => $member['role'],
                    'time_exception' => $member['time_exception'] ?? '',
                ];
            }
        }
        $patrol->crew()->sync($crewData);

        AuditLog::record(
            'patrol.created',
            'Turnos de Flota',
            "Asignación de Patrulla #{$patrol->id} ({$this->filterShift}) para fecha {$this->filterDate}.",
            $patrol,
            ['device_id' => $this->device_id, 'sector_id' => $this->sector_id, 'crew_count' => count($crewData)]
        );

        // Limpiamos el formulario
        $this->reset(['device_id', 'sector_id', 'mission_notes']);
        $this->crewMembers = [];
        $this->addCrewMember();

        session()->flash('message', 'Patrulla asignada exitosamente.');
    }

    public function toggleActive($id)
    {
        abort_unless(auth()->user()?->hasAnyRole(['Administrador', 'Supervisor']), 403);

        $patrol = PatrolAssignment::findOrFail($id);

        if (!$patrol->is_active) {
            PatrolAssignment::where('device_id', $patrol->device_id)
                ->where('id', '!=', $patrol->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        $patrol->update(['is_active' => !$patrol->is_active]);
        session()->flash('message', $patrol->is_active ? 'Turno de patrulla reactivado.' : 'Turno de patrulla finalizado.');
    }

    public function deletePatrol($id)
    {
        abort_unless(auth()->user()?->hasAnyRole(['Administrador', 'Supervisor']), 403);

        try {
            PatrolAssignment::destroy($id);
            session()->flash('message', 'Patrulla eliminada.');
        } catch (\Illuminate\Database\QueryException $e) {
            session()->flash('error', 'No se puede eliminar esta patrulla porque ya tiene procedimientos asociados. Puedes finalizar su turno.');
        }
    }

    public function render()
    {
        return view('livewire.manage-patrols', [
            'devices' => Device::orderBy('name')->get(),
            'sectors' => Sector::orderBy('name')->get(),
            'users' => User::orderBy('name')->get(),
            'shiftBlocks' => $this->getShiftBlocks(),
            'crewRoles' => $this->getCrewRoles(),
            'activePatrols' => PatrolAssignment::with(['device', 'sector', 'crew'])
                ->where('date', $this->filterDate)
                ->where('shift_name', $this->filterShift)
                ->get(),
        ])->layout('layouts.app');
    }
}