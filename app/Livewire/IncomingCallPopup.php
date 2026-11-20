<?php

namespace App\Livewire;

use App\Models\Call;
use App\Models\Procedure;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;
use Livewire\Component;

class IncomingCallPopup extends Component
{
    public $showModal = false;

    public $callerId = '';

    public $extension = '';

    public $callId = null;

    public bool $notificationsMuted = false;

    public int $notificationDurationSeconds = 10;

    // Historial previo del número entrante
    public $previousCallsCount = 0;

    public $prankCallsCount = 0;

    public $recentProcedures = [];

    public function mount(): void
    {
        $user = auth()->user();
        if ($user) {
            $key = $this->muteStorageKey($user->id);
            $this->notificationsMuted = (bool) session($key, Cache::get($key, false));
        }
    }

    // Usamos getListeners para escuchar un canal dinámico (depende del rol o anexo del usuario)
    public function getListeners()
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        // Administradores y Supervisores escuchan todas las llamadas entrantes
        if ($user->hasAnyRole(['Administrador', 'Supervisor'])) {
            return [
                'echo-private:calls.all,NewCallIncoming' => 'handleIncomingCall',
            ];
        }

        $extension = $user->extension ?? '0';

        return [
            // Escuchamos el evento de Laravel Echo en el canal privado del anexo
            "echo-private:calls.{$extension},NewCallIncoming" => 'handleIncomingCall',
        ];
    }

    #[On('toggle-call-notifications')]
    public function toggleNotifications(): void
    {
        $this->notificationsMuted = ! $this->notificationsMuted;

        $user = auth()->user();
        if ($user) {
            $key = $this->muteStorageKey($user->id);
            session([$key => $this->notificationsMuted]);
            Cache::put($key, $this->notificationsMuted, now()->addDays(7));
        }

        if ($this->notificationsMuted) {
            $this->showModal = false;
            $this->dispatch('stop-ringtone');
        }

        $this->dispatch('call-notifications-state-changed', muted: $this->notificationsMuted);
    }

    public function handleIncomingCall($event)
    {
        // Siempre notificamos al banner de llamadas pendientes para que refresque su contador
        $this->dispatch('unattended-calls-updated');

        $user = auth()->user();
        if ($user) {
            $key = $this->muteStorageKey($user->id);
            $this->notificationsMuted = (bool) session($key, Cache::get($key, $this->notificationsMuted));
        }

        // Si el operador desactivó las notificaciones de llamadas, no mostramos popup ni reproducimos sonido
        if ($this->notificationsMuted) {
            $this->showModal = false;

            return;
        }

        // Extraemos los datos del evento que envió Asterisk
        $this->callerId = $event['call']['caller_id'] ?? 'Desconocido';
        $this->extension = $event['call']['extension'] ?? '';
        $this->callId = $event['call']['id'] ?? null;

        // Consultamos historial previo del número si es válido
        if ($this->callerId && $this->callerId !== 'Desconocido') {
            $this->previousCallsCount = Call::where('caller_id', $this->callerId)
                ->when($this->callId, fn ($q) => $q->where('id', '!=', $this->callId))
                ->count();

            $this->prankCallsCount = Call::where('caller_id', $this->callerId)
                ->where('classification', 'Pitanza / Broma')
                ->count();

            $this->recentProcedures = Procedure::with(['procedureType', 'sector'])
                ->where('caller_phone', $this->callerId)
                ->orderBy('created_at', 'desc')
                ->take(3)
                ->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'date' => $p->created_at->format('d/m H:i'),
                    'type' => $p->procedureType->name ?? 'Caso',
                    'status' => $p->status,
                    'address' => $p->exact_address,
                ])->toArray();
        } else {
            $this->previousCallsCount = 0;
            $this->prankCallsCount = 0;
            $this->recentProcedures = [];
        }

        // Mostramos el Pop-up
        $this->showModal = true;

        // Le decimos al navegador que reproduzca el timbre por máximo 10 segundos
        $this->dispatch('play-ringtone', duration: $this->notificationDurationSeconds);
    }

    public function createProcedure()
    {
        $this->showModal = false;
        $this->dispatch('stop-ringtone');
        // IMPORTANTE: Enviamos caller_id (el número) y call_id (el ID de la tabla calls)
        $this->dispatch('openModal', caller_id: $this->callerId, call_id: $this->callId);
    }

    public function dismiss()
    {
        // Oculta la alerta visual y detiene el sonido
        $this->showModal = false;
        $this->dispatch('stop-ringtone');
        $this->dispatch('unattended-calls-updated');
    }

    public function autoDismiss(): void
    {
        $this->showModal = false;
        $this->dispatch('stop-ringtone');
        $this->dispatch('unattended-calls-updated');
    }

    protected function muteStorageKey(int $userId): string
    {
        return "call_notifications_muted_{$userId}";
    }

    public function render()
    {
        return view('livewire.incoming-call-popup');
    }
}
