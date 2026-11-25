<?php

namespace App\Livewire;

use App\Models\Call;
use App\Models\Procedure;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class OperatorReports extends Component
{
    use WithPagination;

    public string $activeTab = 'procedures'; // procedures | calls

    public string $startDate = '';

    public string $endDate = '';

    public string $statusFilter = '';

    public string $search = '';

    /** @var array<int, int|string> */
    public array $selectedProcedureIds = [];

    public bool $includeWatermark = true;

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasAnyRole(['Administrador', 'Supervisor', 'Operador']), 403);

        $this->startDate = Carbon::now()->subDays(15)->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
    }

    public function updatingStartDate(): void
    {
        $this->resetPage('proceduresPage');
        $this->resetPage('callsPage');
        $this->selectedProcedureIds = [];
    }

    public function updatingEndDate(): void
    {
        $this->resetPage('proceduresPage');
        $this->resetPage('callsPage');
        $this->selectedProcedureIds = [];
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage('proceduresPage');
        $this->selectedProcedureIds = [];
    }

    public function updatingSearch(): void
    {
        $this->resetPage('proceduresPage');
        $this->resetPage('callsPage');
    }

    public function selectAllFilteredProcedures(): void
    {
        $ids = $this->baseProceduresQuery()
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        $this->selectedProcedureIds = $ids;
    }

    public function clearSelection(): void
    {
        $this->selectedProcedureIds = [];
    }

    public function getSelectedBatchPdfUrlProperty(): ?string
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $this->selectedProcedureIds))));
        if (empty($ids)) {
            return null;
        }

        return route('procedures.print-batch', [
            'ids' => implode(',', $ids),
            'watermark' => $this->includeWatermark ? '1' : '0',
            'from' => $this->startDate,
            'to' => $this->endDate,
        ]);
    }

    protected function baseProceduresQuery()
    {
        $userId = (int) Auth::id();
        $term = trim($this->search);

        return Procedure::with(['sector', 'procedureType', 'device'])
            ->withCount(['logs', 'evidences'])
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhereHas('logs', fn ($logQ) => $logQ->where('user_id', $userId));
            })
            ->when($this->startDate !== '', fn ($q) => $q->whereDate('created_at', '>=', $this->startDate))
            ->when($this->endDate !== '', fn ($q) => $q->whereDate('created_at', '<=', $this->endDate))
            ->when($this->statusFilter !== '', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($sub) use ($term) {
                    if (is_numeric($term)) {
                        $sub->where('id', (int) $term)
                            ->orWhere('caller_phone', 'like', "%{$term}%");
                    } else {
                        $sub->where('exact_address', 'like', "%{$term}%")
                            ->orWhere('caller_name', 'like', "%{$term}%")
                            ->orWhereHas('procedureType', fn ($pt) => $pt->where('name', 'like', "%{$term}%"))
                            ->orWhereHas('sector', fn ($s) => $s->where('name', 'like', "%{$term}%"));
                    }
                });
            });
    }

    protected function baseCallsQuery()
    {
        $user = Auth::user();
        $userId = (int) $user->id;
        $extension = trim((string) ($user->extension ?? ''));
        $term = trim($this->search);

        return Call::with('procedure')
            ->where(function ($q) use ($userId, $extension) {
                if ($extension !== '') {
                    $q->where('extension', $extension)
                        ->orWhereHas('procedure', fn ($p) => $p->where('user_id', $userId));
                } else {
                    $q->whereHas('procedure', fn ($p) => $p->where('user_id', $userId));
                }
            })
            ->when($this->startDate !== '', fn ($q) => $q->whereDate('created_at', '>=', $this->startDate))
            ->when($this->endDate !== '', fn ($q) => $q->whereDate('created_at', '<=', $this->endDate))
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($sub) use ($term) {
                    $sub->where('caller_id', 'like', "%{$term}%")
                        ->orWhere('classification', 'like', "%{$term}%")
                        ->orWhere('notes', 'like', "%{$term}%");
                });
            });
    }

    public function render()
    {
        $proceduresBase = $this->baseProceduresQuery();
        $totalMyProcedures = (clone $proceduresBase)->count();
        $closedMyProcedures = (clone $proceduresBase)->where('status', 'Cerrado')->count();
        $procedures = (clone $proceduresBase)
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'proceduresPage');

        $callsBase = $this->baseCallsQuery();
        $totalMyCalls = (clone $callsBase)->count();
        $linkedMyCalls = (clone $callsBase)->whereNotNull('procedure_id')->count();
        $calls = (clone $callsBase)
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'callsPage');

        return view('livewire.operator-reports', [
            'procedures' => $procedures,
            'calls' => $calls,
            'totalMyProcedures' => $totalMyProcedures,
            'closedMyProcedures' => $closedMyProcedures,
            'totalMyCalls' => $totalMyCalls,
            'linkedMyCalls' => $linkedMyCalls,
        ])->layout('layouts.app');
    }
}
