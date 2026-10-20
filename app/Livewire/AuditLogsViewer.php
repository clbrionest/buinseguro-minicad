<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\AuditLog;
use App\Models\User;

class AuditLogsViewer extends Component
{
    use WithPagination;

    public $search = '';
    public $filterModule = '';
    public $filterUser = '';
    public $filterDate = '';

    public function mount()
    {
        abort_unless(auth()->user()?->hasAnyRole(['Administrador', 'Supervisor']), 403);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterModule()
    {
        $this->resetPage();
    }

    public function updatingFilterUser()
    {
        $this->resetPage();
    }

    public function updatingFilterDate()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'filterModule', 'filterUser', 'filterDate']);
        $this->resetPage();
    }

    public function render()
    {
        $query = AuditLog::with('user')
            ->when($this->filterModule, fn($q) => $q->where('module', $this->filterModule))
            ->when($this->filterUser, fn($q) => $q->where('user_id', $this->filterUser))
            ->when($this->filterDate, fn($q) => $q->whereDate('created_at', $this->filterDate))
            ->when(trim($this->search) !== '', function ($q) {
                $term = trim($this->search);
                $q->where(function ($sub) use ($term) {
                    $sub->where('description', 'like', "%{$term}%")
                        ->orWhere('action', 'like', "%{$term}%")
                        ->orWhere('ip_address', 'like', "%{$term}%");
                });
            })
            ->orderByDesc('created_at');

        $logs = $query->paginate(20);

        $modules = AuditLog::select('module')
            ->distinct()
            ->orderBy('module')
            ->pluck('module');

        $users = User::orderBy('name')->get(['id', 'name', 'email']);

        $securityAlertsCount = AuditLog::where('action', 'security.denied')
            ->whereDate('created_at', today())
            ->count();

        $todayEventsCount = AuditLog::whereDate('created_at', today())->count();

        return view('livewire.audit-logs-viewer', [
            'logs' => $logs,
            'modules' => $modules,
            'users' => $users,
            'securityAlertsCount' => $securityAlertsCount,
            'todayEventsCount' => $todayEventsCount,
        ])->layout('layouts.app');
    }
}
