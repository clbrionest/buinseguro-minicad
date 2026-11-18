<?php

namespace App\Livewire;

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
    public function checkDispatches(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->hasAnyRole(['Inspector', 'Supervisor'])) {
            $this->showEmergencyModal = false;
            $this->alertProcedure = null;

            return;
        }

        $assignedDeviceIds = $user->assignedDeviceIds();
        if (empty($assignedDeviceIds)) {
            $this->showEmergencyModal = false;
            $this->alertProcedure = null;

            return;
        }

        $sessionKey = 'acknowledged_dispatches_'.$user->id;
        $acknowledged = session($sessionKey, []);

        $pendingDispatch = Procedure::with(['procedureType', 'sector', 'device'])
            ->whereIn('device_id', $assignedDeviceIds)
            ->where('status', 'En Camino')
            ->latest('dispatched_at')
            ->latest('id')
            ->get()
            ->first(function (Procedure $proc) use ($acknowledged) {
                $key = $this->dispatchKey($proc);

                return ! in_array($key, $acknowledged, true);
            });

        if ($pendingDispatch) {
            $newKey = $this->dispatchKey($pendingDispatch);
            $wasAlreadyShowingSame = $this->showEmergencyModal
                && isset($this->alertProcedure['key'])
                && $this->alertProcedure['key'] === $newKey;

            $this->alertProcedure = [
                'key' => $newKey,
                'id' => $pendingDispatch->id,
                'type' => $pendingDispatch->procedureType?->name ?? 'Procedimiento de Emergencia',
                'address' => $pendingDispatch->exact_address ?: 'Dirección en ficha',
                'reference' => $pendingDispatch->address_reference ?: '',
                'sector' => $pendingDispatch->sector?->name ?? 'Comuna de Buin',
                'device_name' => $pendingDispatch->device?->name ?? 'Unidad Asignada',
                'dispatched_time' => $pendingDispatch->dispatched_at?->format('H:i:s') ?? now()->format('H:i:s'),
                'url' => route('procedures.show', $pendingDispatch->id),
            ];
            $this->showEmergencyModal = true;

            if (! $wasAlreadyShowingSame) {
                $this->dispatch('play-emergency-siren');
            }
        } else {
            if ($this->showEmergencyModal) {
                $this->dispatch('stop-emergency-siren');
            }
            $this->showEmergencyModal = false;
            $this->alertProcedure = null;
        }
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

    protected function dispatchKey(Procedure $procedure): string
    {
        $ts = $procedure->dispatched_at ? $procedure->dispatched_at->timestamp : $procedure->id;

        return $procedure->id.'_'.$procedure->device_id.'_'.$ts;
    }

    public function render()
    {
        return view('livewire.emergency-dispatch-alert');
    }
}
