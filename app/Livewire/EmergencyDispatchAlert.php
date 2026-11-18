<?php

namespace App\Livewire;

use App\Models\PreventivePatrol;
use App\Models\Procedure;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class EmergencyDispatchAlert extends Component
{
    public bool $showEmergencyModal = false;

    public ?array $alertProcedure = null;

    public function mount(): void
    {
        $this->checkDispatches();
    }

    #[On('procedure-dispatched')]
    #[On('procedure-created')]
    #[On('preventive-patrol-created')]
    public function checkDispatches(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->hasAnyRole(['Inspector', 'Supervisor'])) {
            $this->showEmergencyModal = false;
            $this->alertProcedure = null;

            return;
        }

        $assignedDeviceIds = $user->assignedDeviceIds();
        $sessionKey = 'acknowledged_dispatches_'.$user->id;
        $acknowledged = session($sessionKey, []);

        // 1. Verificar procedimientos asignados al móvil del turno o traspasados directamente al inspector
        $pendingProcedure = Procedure::with(['procedureType', 'sector', 'device'])
            ->where('status', 'En Camino')
            ->where(function ($q) use ($assignedDeviceIds, $user) {
                if (! empty($assignedDeviceIds)) {
                    $q->whereIn('device_id', $assignedDeviceIds)
                        ->orWhere('metadata->_assigned_inspector_id', $user->id);
                } else {
                    $q->where('metadata->_assigned_inspector_id', $user->id);
                }
            })
            ->latest('dispatched_at')
            ->latest('id')
            ->get()
            ->first(function (Procedure $proc) use ($acknowledged) {
                $key = $this->procedureDispatchKey($proc);

                return ! in_array($key, $acknowledged, true);
            });

        if ($pendingProcedure) {
            $newKey = $this->procedureDispatchKey($pendingProcedure);
            $wasAlreadyShowingSame = $this->showEmergencyModal
                && isset($this->alertProcedure['key'])
                && $this->alertProcedure['key'] === $newKey;

            $isTransferred = isset($pendingProcedure->metadata['_assigned_inspector_id'])
                && (int) $pendingProcedure->metadata['_assigned_inspector_id'] === (int) $user->id;

            $this->alertProcedure = [
                'key' => $newKey,
                'kind' => 'procedure',
                'header_title' => $isTransferred ? 'Procedimiento Traspasado / Asignado' : 'Procedimiento Asignado a Unidad',
                'badge' => '#'.$pendingProcedure->id,
                'id' => $pendingProcedure->id,
                'type' => $pendingProcedure->procedureType?->name ?? 'Procedimiento Operativo',
                'address' => $pendingProcedure->exact_address ?: 'Dirección en ficha',
                'reference' => $pendingProcedure->address_reference ?: '',
                'sector' => $pendingProcedure->sector?->name ?? 'Comuna de Buin',
                'device_name' => $pendingProcedure->device?->name ?? 'Unidad Asignada',
                'dispatched_time' => $pendingProcedure->dispatched_at?->format('H:i:s') ?? now()->format('H:i:s'),
                'action_label' => 'Ver Procedimiento →',
                'url' => route('procedures.show', $pendingProcedure->id),
            ];
            $this->showEmergencyModal = true;

            if (! $wasAlreadyShowingSame) {
                $this->dispatch('play-emergency-siren');
            }

            return;
        }

        // 2. Verificar patrullajes / rutas preventivas asignadas al móvil del turno
        $pendingPatrol = null;
        if (! empty($assignedDeviceIds)) {
            $pendingPatrol = PreventivePatrol::with(['sector', 'device', 'checkpoints'])
                ->whereIn('device_id', $assignedDeviceIds)
                ->where('status', 'En Curso')
                ->latest('started_at')
                ->latest('id')
                ->get()
                ->first(function (PreventivePatrol $patrol) use ($acknowledged) {
                    $key = $this->patrolDispatchKey($patrol);

                    return ! in_array($key, $acknowledged, true);
                });
        }

        if ($pendingPatrol) {
            $newKey = $this->patrolDispatchKey($pendingPatrol);
            $wasAlreadyShowingSame = $this->showEmergencyModal
                && isset($this->alertProcedure['key'])
                && $this->alertProcedure['key'] === $newKey;

            $checkpointsCount = $pendingPatrol->checkpoints->count();

            $this->alertProcedure = [
                'key' => $newKey,
                'kind' => 'patrol',
                'header_title' => 'Patrullaje / Ruta Asignada',
                'badge' => $pendingPatrol->folio,
                'id' => $pendingPatrol->id,
                'type' => $pendingPatrol->patrol_type,
                'address' => $pendingPatrol->mission_objective ?: 'Recorrido preventivo asignado en el sector.',
                'reference' => "{$checkpointsCount} punto(s) / hito(s) de control en ruta",
                'sector' => $pendingPatrol->sector?->name ?? 'Comuna de Buin',
                'device_name' => $pendingPatrol->device?->name ?? 'Unidad Asignada',
                'dispatched_time' => $pendingPatrol->started_at?->format('H:i:s') ?? now()->format('H:i:s'),
                'action_label' => 'Ver Ruta / Patrullaje →',
                'url' => route('patrols.preventive.show', $pendingPatrol->id),
            ];
            $this->showEmergencyModal = true;

            if (! $wasAlreadyShowingSame) {
                $this->dispatch('play-emergency-siren');
            }

            return;
        }

        if ($this->showEmergencyModal) {
            $this->dispatch('stop-emergency-siren');
        }
        $this->showEmergencyModal = false;
        $this->alertProcedure = null;
    }

    public function acknowledgeDispatch(): void
    {
        $userId = Auth::id();
        if ($userId && $this->alertProcedure && isset($this->alertProcedure['key'])) {
            $sessionKey = 'acknowledged_dispatches_'.$userId;
            $acknowledged = session($sessionKey, []);
            $acknowledged[] = $this->alertProcedure['key'];
            session([$sessionKey => array_values(array_unique($acknowledged))]);
        }

        $this->showEmergencyModal = false;
        $this->alertProcedure = null;
        $this->dispatch('stop-emergency-siren');
    }

    protected function procedureDispatchKey(Procedure $procedure): string
    {
        $ts = $procedure->dispatched_at ? $procedure->dispatched_at->timestamp : $procedure->id;

        return 'proc_'.$procedure->id.'_'.$procedure->device_id.'_'.$ts;
    }

    protected function patrolDispatchKey(PreventivePatrol $patrol): string
    {
        $ts = $patrol->started_at ? $patrol->started_at->timestamp : $patrol->id;

        return 'patrol_'.$patrol->id.'_'.$patrol->device_id.'_'.$ts;
    }

    public function render()
    {
        return view('livewire.emergency-dispatch-alert');
    }
}
