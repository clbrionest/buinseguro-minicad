<?php

namespace App\Livewire;

use App\Models\Call;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class UnattendedCallsBanner extends Component
{
    #[On('unattended-calls-updated')]
    #[On('procedure-created')]
    #[On('call-classified')]
    #[On('call-linked')]
    public function refreshCount(): void
    {
        // Livewire re-renders automatically when event is received
    }

    public function render()
    {
        $user = Auth::user();
        $unattendedCount = 0;

        if ($user && $user->hasAnyRole(['Administrador', 'Supervisor', 'Operador'])) {
            $unattendedCount = Call::query()
                ->whereNull('procedure_id')
                ->where(function ($query) {
                    $query->whereNull('classification')
                        ->orWhere('classification', '');
                })
                ->count();
        }

        return view('livewire.unattended-calls-banner', [
            'unattendedCount' => $unattendedCount,
        ]);
    }
}
