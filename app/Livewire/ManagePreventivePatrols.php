<?php

namespace App\Livewire;

use App\Models\PreventivePatrol;
use App\Models\Procedure;
use App\Models\Sector;
use Carbon\Carbon;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ManagePreventivePatrols extends Component
{
    use WithPagination;

    #[Url(as: 'estado')]
    public $filterStatus = 'all';

    #[Url(as: 'sector')]
    public $filterSector = '';

    #[Url(as: 'fecha')]
    public $filterDate = '';

    #[Url(as: 'q')]
    public $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function updatingFilterSector()
    {
        $this->resetPage();
    }

    public function updatingFilterDate()
    {
        $this->resetPage();
    }

    public function completePatrol($id)
    {
        $patrol = PreventivePatrol::findOrFail($id);
        $patrol->update([
            'status' => 'Completado',
            'ended_at' => now(),
        ]);

        session()->flash('message', "Patrullaje {$patrol->folio} marcado como Completado.");
    }

    public function render()
    {
        $user = auth()->user();

        $query = PreventivePatrol::with(['device', 'sector', 'user', 'checkpoints', 'procedures', 'patrolAssignment.crew'])
            ->latest('started_at');

        // Restricción por rol de usuario
        if ($user && ! $user->hasAnyRole(['Administrador', 'Supervisor'])) {
            $allowed = $user->sectors->pluck('id');
            $query->whereIn('sector_id', $allowed);
        }

        if ($this->filterStatus && $this->filterStatus !== 'all') {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterSector) {
            $query->where('sector_id', $this->filterSector);
        }

        if ($this->filterDate) {
            $query->whereDate('started_at', $this->filterDate);
        }

        if (! empty(trim($this->search))) {
            $term = '%'.trim($this->search).'%';
            $query->where(function ($q) use ($term) {
                $q->where('folio', 'like', $term)
                    ->orWhere('mission_objective', 'like', $term)
                    ->orWhereHas('device', fn ($d) => $d->where('name', 'like', $term));
            });
        }

        $patrols = $query->paginate(12);

        // Métricas para los KPI cards
        $today = Carbon::today();
        $baseMetrics = PreventivePatrol::query();
        if ($user && ! $user->hasAnyRole(['Administrador', 'Supervisor'])) {
            $allowed = $user->sectors->pluck('id');
            $baseMetrics->whereIn('sector_id', $allowed);
        }

        $activeCount = (clone $baseMetrics)->where('status', 'En Curso')->count();
        $completedToday = (clone $baseMetrics)->where('status', 'Completado')->whereDate('ended_at', $today)->count();
        $proceduresFromPatrolsToday = Procedure::whereNotNull('preventive_patrol_id')->whereDate('created_at', $today)->count();

        $sectors = Sector::orderBy('name')->get();

        return view('livewire.manage-preventive-patrols', [
            'patrols' => $patrols,
            'activeCount' => $activeCount,
            'completedToday' => $completedToday,
            'proceduresFromPatrolsToday' => $proceduresFromPatrolsToday,
            'sectors' => $sectors,
        ])->layout('layouts.app');
    }
}
