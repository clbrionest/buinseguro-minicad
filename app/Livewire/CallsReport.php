<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Call;
use Livewire\WithPagination;

class CallsReport extends Component
{
    use WithPagination;

    public $search = '';
    
    // Variables para el Modal de Tipificación
    public $isClassifyModalOpen = false;
    public $selectedCallId = null;
    public $classification = '';
    public $notes = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    // Abre el modal y carga los datos si ya tenía clasificación
    public function openClassifyModal($id)
    {
        $call = Call::findOrFail($id);
        $this->selectedCallId = $call->id;
        $this->classification = $call->classification ?? '';
        $this->notes = $call->notes ?? '';
        $this->isClassifyModalOpen = true;
    }

    public function closeClassifyModal()
    {
        $this->isClassifyModalOpen = false;
        $this->reset(['selectedCallId', 'classification', 'notes']);
    }

    public function saveClassification()
    {
        $this->validate([
            'classification' => 'required|string',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($this->selectedCallId) {
            Call::where('id', $this->selectedCallId)->update([
                'classification' => $this->classification,
                'notes' => $this->notes,
            ]);
        }

        $this->closeClassifyModal();
        
        // Un mensaje de éxito opcional (si usas algún sistema de alertas en tu app)
        session()->flash('message', 'Llamada tipificada correctamente.');
    }

    public function render()
    {
        $calls = Call::with('procedure')
            ->where(function($query) {
                $query->where('caller_id', 'like', '%' . $this->search . '%')
                      ->orWhere('extension', 'like', '%' . $this->search . '%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $callClassifications = \App\Models\SystemCatalogItem::namesFor('call_classifications', [
            'Información General',
            'Pitanza / Broma',
            'Llamada Cortada',
            'Número Equivocado',
            'Derivado a otra Institución',
            'Otro',
        ]);

        return view('livewire.calls-report', [
            'calls' => $calls,
            'callClassifications' => $callClassifications,
        ])->layout('layouts.app');
    }
}