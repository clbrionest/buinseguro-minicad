<?php

namespace App\Livewire;

use Livewire\Component;

class IncomingCallPopup extends Component
{
    public $showModal = false;
    public $callerId = '';
    public $extension = '';
    public $callId = null;

    // Historial previo del número entrante
    public $previousCallsCount = 0;
    public $prankCallsCount = 0;
    public $recentProcedures = [];

    // Usamos getListeners para escuchar un canal dinámico (depende del rol o anexo del usuario)
    public function getListeners()
    {
        $user = auth()->user();

        if (!$user) {
            return [];
        }

        // Administradores y Supervisores escuchan todas las llamadas entrantes
        if ($user->hasAnyRole(['Administrador', 'Supervisor'])) {
            return [
                "echo-private:calls.all,NewCallIncoming" => 'handleIncomingCall',
            ];
        }

        $extension = $user->extension ?? '0';

        return [
            // Escuchamos el evento de Laravel Echo en el canal privado del anexo
            "echo-private:calls.{$extension},NewCallIncoming" => 'handleIncomingCall',
        ];
    }

    public function handleIncomingCall($event)
    {
        // Extraemos los datos del evento que envió Asterisk
        $this->callerId = $event['call']['caller_id'] ?? 'Desconocido';
        $this->extension = $event['call']['extension'] ?? '';
        $this->callId = $event['call']['id'] ?? null;

        // Consultamos historial previo del número si es válido
        if ($this->callerId && $this->callerId !== 'Desconocido') {
            $this->previousCallsCount = \App\Models\Call::where('caller_id', $this->callerId)
                ->when($this->callId, fn($q) => $q->where('id', '!=', $this->callId))
                ->count();

            $this->prankCallsCount = \App\Models\Call::where('caller_id', $this->callerId)
                ->where('classification', 'Pitanza / Broma')
                ->count();

            $this->recentProcedures = \App\Models\Procedure::with(['procedureType', 'sector'])
                ->where('caller_phone', $this->callerId)
                ->orderBy('created_at', 'desc')
                ->take(3)
                ->get()
                ->map(fn($p) => [
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
        
        // Le decimos al navegador que reproduzca un sonido de timbre
        $this->dispatch('play-ringtone');
    }

    public function createProcedure()
    {
        $this->showModal = false;
        // IMPORTANTE: Enviamos caller_id (el número) y call_id (el ID de la tabla calls)
        $this->dispatch('openModal', caller_id: $this->callerId, call_id: $this->callId);
    }

    public function dismiss()
    {
        // Solo oculta la alerta visual
        $this->showModal = false;
    }

    public function render()
    {
        return view('livewire.incoming-call-popup');
    }
}