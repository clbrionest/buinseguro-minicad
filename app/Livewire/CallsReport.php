<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Call;
use App\Models\Procedure;
use App\Models\ProcedureLog;
use App\Models\SystemCatalogItem;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class CallsReport extends Component
{
    use WithPagination;

    public $search = '';

    #[Url(as: 'estado')]
    public $filterStatus = '';

    // Variables para el Modal de Tipificación
    public $isClassifyModalOpen = false;

    public $selectedCallId = null;

    public $classification = '';

    public $notes = '';

    // Variables para el Modal de Vinculación a Caso Existente
    public $isLinkModalOpen = false;

    public $selectedCallForLinkingId = null;

    public $selectedCallForLinking = null;

    public $procedureSearch = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasAnyRole(['Administrador', 'Supervisor', 'Operador']), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingProcedureSearch(): void
    {
        // Se mantiene en la búsqueda del modal
    }

    #[On('procedure-created')]
    public function handleProcedureCreated(): void
    {
        $this->resetPage();
    }

    // Modal de Tipificación
    public function openClassifyModal(int $id): void
    {
        $call = Call::findOrFail($id);
        $this->selectedCallId = $call->id;
        $this->classification = $call->classification ?? '';
        $this->notes = $call->notes ?? '';
        $this->isClassifyModalOpen = true;
    }

    public function closeClassifyModal(): void
    {
        $this->isClassifyModalOpen = false;
        $this->reset(['selectedCallId', 'classification', 'notes']);
    }

    public function saveClassification(): void
    {
        $this->validate([
            'classification' => 'required|string',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($this->selectedCallId) {
            $call = Call::findOrFail($this->selectedCallId);
            $call->update([
                'classification' => $this->classification,
                'notes' => $this->notes,
            ]);

            AuditLog::record(
                'call.classified',
                'Telefonía PBX',
                "Llamada #{$call->id} clasificada como '{$this->classification}'.",
                $call,
                ['classification' => $this->classification, 'notes' => $this->notes]
            );
        }

        $this->closeClassifyModal();
        $this->dispatch('unattended-calls-updated');
        session()->flash('message', 'Llamada tipificada correctamente.');
    }

    // Modal de Vinculación a Caso Existente
    public function openLinkModal(int $id): void
    {
        $call = Call::findOrFail($id);
        $this->selectedCallForLinkingId = $call->id;
        $this->selectedCallForLinking = $call;
        $this->procedureSearch = '';
        $this->isLinkModalOpen = true;
    }

    public function closeLinkModal(): void
    {
        $this->isLinkModalOpen = false;
        $this->reset(['selectedCallForLinkingId', 'selectedCallForLinking', 'procedureSearch']);
    }

    public function linkCallToProcedure(int $procedureId): void
    {
        $call = Call::findOrFail($this->selectedCallForLinkingId);
        $procedure = Procedure::with(['sector', 'procedureType'])->findOrFail($procedureId);

        $call->update([
            'procedure_id' => $procedure->id,
        ]);

        // Si el procedimiento aún no tenía grabación vinculada, asignamos esta llamada
        if (empty($procedure->call_id)) {
            $procedure->update([
                'call_id' => $call->id,
            ]);
        }

        ProcedureLog::create([
            'procedure_id' => $procedure->id,
            'user_id' => auth()->id(),
            'action_type' => 'Llamada Vinculada',
            'description' => "Se vinculó llamada telefónica #{$call->id} (Origen: {$call->caller_id}, Anexo: {$call->extension}) al procedimiento.",
        ]);

        AuditLog::record(
            'call.linked_to_procedure',
            'Telefonía PBX',
            "Llamada #{$call->id} ({$call->caller_id}) vinculada manualmente al Caso Folio #{$procedure->id}.",
            $call,
            [
                'procedure_id' => $procedure->id,
                'call_id' => $call->id,
                'caller_id' => $call->caller_id,
            ]
        );

        $this->closeLinkModal();
        $this->dispatch('unattended-calls-updated');
        session()->flash('message', "Llamada #{$call->id} vinculada exitosamente al Caso #{$procedure->id}.");
    }

    public function unlinkCall(int $callId): void
    {
        $call = Call::findOrFail($callId);
        $procedureId = $call->procedure_id;

        $call->update([
            'procedure_id' => null,
        ]);

        if ($procedureId) {
            $procedure = Procedure::find($procedureId);
            if ($procedure && (int) $procedure->call_id === (int) $call->id) {
                $procedure->update([
                    'call_id' => null,
                ]);
            }

            ProcedureLog::create([
                'procedure_id' => $procedureId,
                'user_id' => auth()->id(),
                'action_type' => 'Llamada Desvinculada',
                'description' => "Se desvinculó la llamada telefónica #{$call->id} (Origen: {$call->caller_id}) del procedimiento.",
            ]);

            AuditLog::record(
                'call.unlinked_from_procedure',
                'Telefonía PBX',
                "Llamada #{$call->id} desvinculada del Caso Folio #{$procedureId}.",
                $call,
                ['procedure_id' => $procedureId, 'call_id' => $call->id]
            );
        }

        $this->dispatch('unattended-calls-updated');
        session()->flash('message', "Llamada #{$call->id} desvinculada del caso.");
    }

    public function render()
    {
        $callsQuery = Call::with('procedure')
            ->where(function ($query) {
                $query->where('caller_id', 'like', '%'.$this->search.'%')
                    ->orWhere('extension', 'like', '%'.$this->search.'%');
            });

        if ($this->filterStatus === 'linked') {
            $callsQuery->whereNotNull('procedure_id');
        } elseif ($this->filterStatus === 'unlinked') {
            $callsQuery->whereNull('procedure_id');
        }

        $calls = $callsQuery->orderBy('created_at', 'desc')->paginate(15);

        $callClassifications = SystemCatalogItem::namesFor('call_classifications', [
            'Información General',
            'Pitanza / Broma',
            'Llamada Cortada',
            'Número Equivocado',
            'Derivado a otra Institución',
            'Otro',
        ]);

        // Consulta de casos candidatos para el modal de vinculación
        $availableProcedures = collect();
        if ($this->isLinkModalOpen && $this->selectedCallForLinking) {
            $term = trim($this->procedureSearch);
            $procQuery = Procedure::with(['procedureType', 'sector'])
                ->orderBy('created_at', 'desc');

            if ($term !== '') {
                $procQuery->where(function ($q) use ($term) {
                    if (is_numeric($term)) {
                        $q->where('id', (int) $term)
                            ->orWhere('caller_phone', 'like', "%{$term}%");
                    } else {
                        $q->where('exact_address', 'like', "%{$term}%")
                            ->orWhere('caller_name', 'like', "%{$term}%")
                            ->orWhere('caller_phone', 'like', "%{$term}%")
                            ->orWhereHas('procedureType', fn ($sub) => $sub->where('name', 'like', "%{$term}%"));
                    }
                });
                $availableProcedures = $procQuery->take(20)->get();
            } else {
                // Sugerencias: primero casos con el mismo número telefónico
                $callerPhone = $this->selectedCallForLinking->caller_id;
                if ($callerPhone && $callerPhone !== 'Desconocido' && strlen($callerPhone) >= 4) {
                    $sameCaller = Procedure::with(['procedureType', 'sector'])
                        ->where('caller_phone', $callerPhone)
                        ->orderBy('created_at', 'desc')
                        ->take(5)
                        ->get();

                    $otherRecent = Procedure::with(['procedureType', 'sector'])
                        ->where('caller_phone', '!=', $callerPhone)
                        ->orderBy('created_at', 'desc')
                        ->take(10)
                        ->get();

                    $availableProcedures = $sameCaller->concat($otherRecent);
                } else {
                    $availableProcedures = $procQuery->take(15)->get();
                }
            }
        }

        return view('livewire.calls-report', [
            'calls' => $calls,
            'callClassifications' => $callClassifications,
            'availableProcedures' => $availableProcedures,
        ])->layout('layouts.app');
    }
}
