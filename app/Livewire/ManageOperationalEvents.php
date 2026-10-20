<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\OperationalEvent;
use App\Models\EventReport;
use App\Models\Sector;
use App\Models\Device;
use Illuminate\Support\Facades\Auth;

class ManageOperationalEvents extends Component
{
    use WithPagination;

    // Evento actualmente seleccionado
    public $selectedEventId = null;

    // Modal para crear nuevo Evento Masivo (Admin / Supervisor)
    public $isNewEventModalOpen = false;
    public $newEventName = '';
    public $newEventType = 'Frente de Mal Tiempo';
    public $newEventDescription = '';

    // Formulario de Recolección Rápida de Reportes Vecinales
    public $category = 'Inundación / Anegamiento';
    public $urgency = 'Media';
    public $sector_id = '';
    public $exact_address = '';
    public $address_reference = '';
    public $latitude = '';
    public $longitude = '';
    public $caller_name = '';
    public $caller_phone = '';
    public $device_id = '';
    public $notes = '';

    // Filtros rápidos de visualización
    public $filterCategory = '';
    public $filterStatus = '';
    public $filterSector = '';
    public $search = '';

    public const CATEGORIES = [
        'Inundación / Anegamiento' => ['icon' => '🌊', 'color' => 'bg-blue-100 text-blue-800 border-blue-300'],
        'Árbol o Rama Caída' => ['icon' => '🌳', 'color' => 'bg-green-100 text-green-800 border-green-300'],
        'Corte de Luz / Tendido Eléctrico' => ['icon' => '⚡', 'color' => 'bg-yellow-100 text-yellow-800 border-yellow-300'],
        'Voladura de Techumbre / Vivienda' => ['icon' => '🏠', 'color' => 'bg-orange-100 text-orange-800 border-orange-300'],
        'Ruta Cortada / Semáforo / Socavón' => ['icon' => '🚧', 'color' => 'bg-red-100 text-red-800 border-red-300'],
        'Apoyo Adulto Mayor / Social' => ['icon' => '🛟', 'color' => 'bg-purple-100 text-purple-800 border-purple-300'],
        'Otro Reporte de Contingencia' => ['icon' => '📌', 'color' => 'bg-gray-100 text-gray-800 border-gray-300'],
    ];

    public function getDynamicCategories(): array
    {
        try {
            $dbCats = \App\Models\EventCategory::where('is_active', true)->orderBy('id')->get();
            if ($dbCats->count() > 0) {
                $map = [];
                foreach ($dbCats as $c) {
                    $map[$c->name] = [
                        'icon' => $c->icon ?: '📌',
                        'color' => $c->badge_classes,
                    ];
                }
                return $map;
            }
        } catch (\Throwable $e) {
            // Fallback seguro si la tabla aún no se ha migrado
        }

        return self::CATEGORIES;
    }

    public function mount()
    {
        $cats = array_keys($this->getDynamicCategories());
        if (!empty($cats)) {
            $this->category = $cats[0];
        }

        $activeEvent = OperationalEvent::where('is_active', true)->latest()->first()
            ?? OperationalEvent::latest()->first();

        if ($activeEvent) {
            $this->selectedEventId = $activeEvent->id;
        }
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterCategory() { $this->resetPage(); }
    public function updatingFilterStatus() { $this->resetPage(); }
    public function updatingFilterSector() { $this->resetPage(); }

    public function toggleCategoryFilter($cat)
    {
        $this->filterCategory = ($this->filterCategory === $cat) ? '' : $cat;
        $this->resetPage();
    }

    public function createEvent()
    {
        if (!auth()->user()->hasAnyRole(['Administrador', 'Supervisor'])) {
            abort(403);
        }

        $this->validate([
            'newEventName' => 'required|string|max:255',
            'newEventType' => 'required|string|max:100',
        ]);

        // Desactivamos otros eventos activos si se inicia uno nuevo
        OperationalEvent::where('is_active', true)->update([
            'is_active' => false,
            'closed_at' => now(),
        ]);

        $event = OperationalEvent::create([
            'name' => $this->newEventName,
            'event_type' => $this->newEventType,
            'description' => $this->newEventDescription,
            'is_active' => true,
            'user_id' => Auth::id(),
        ]);

        $this->selectedEventId = $event->id;
        $this->isNewEventModalOpen = false;
        $this->reset(['newEventName', 'newEventDescription']);
        $this->newEventType = 'Frente de Mal Tiempo';

        session()->flash('message', 'Evento masivo activado. Ya se pueden recolectar reportes vecinales.');
    }

    public function toggleEventStatus($eventId)
    {
        if (!auth()->user()->hasAnyRole(['Administrador', 'Supervisor'])) {
            abort(403);
        }

        $event = OperationalEvent::findOrFail($eventId);
        if (!$event->is_active) {
            OperationalEvent::where('id', '!=', $event->id)->where('is_active', true)->update([
                'is_active' => false,
                'closed_at' => now(),
            ]);
            $event->update(['is_active' => true, 'closed_at' => null]);
            session()->flash('message', 'Evento masivo reactivado.');
        } else {
            $event->update(['is_active' => false, 'closed_at' => now()]);
            session()->flash('message', 'Evento masivo finalizado.');
        }
    }

    public function saveQuickReport()
    {
        $this->validate([
            'selectedEventId' => 'required|exists:operational_events,id',
            'category' => 'required|string',
            'urgency' => 'required|in:Alta,Media,Baja',
            'exact_address' => 'required|string|max:255',
        ], [
            'exact_address.required' => 'Ingrese la dirección o intersección del reporte.',
        ]);

        $status = $this->device_id ? 'En Atención' : 'Pendiente';

        EventReport::create([
            'operational_event_id' => $this->selectedEventId,
            'user_id' => Auth::id(),
            'category' => $this->category,
            'urgency' => $this->urgency,
            'sector_id' => $this->sector_id ?: null,
            'exact_address' => $this->exact_address,
            'address_reference' => $this->address_reference,
            'latitude' => $this->latitude ?: null,
            'longitude' => $this->longitude ?: null,
            'caller_name' => $this->caller_name,
            'caller_phone' => $this->caller_phone,
            'device_id' => $this->device_id ?: null,
            'status' => $status,
            'notes' => $this->notes,
            'dispatched_at' => $this->device_id ? now() : null,
        ]);

        // Limpiamos solo los datos puntuales del llamado para permitir ingreso continuo ultra rápido
        $this->reset([
            'exact_address',
            'address_reference',
            'latitude',
            'longitude',
            'caller_name',
            'caller_phone',
            'device_id',
            'notes',
        ]);
        $this->urgency = 'Media';

        $this->dispatch('report-saved');
        session()->flash('message', 'Reporte vecinal registrado en el evento.');
    }

    public function assignDeviceToReport($reportId, $deviceId)
    {
        $report = EventReport::findOrFail($reportId);
        $deviceId = $deviceId ?: null;

        $updates = ['device_id' => $deviceId];
        if ($deviceId && $report->status === 'Pendiente') {
            $updates['status'] = 'En Atención';
            $updates['dispatched_at'] = $report->dispatched_at ?? now();
        } elseif (!$deviceId && $report->status === 'En Atención') {
            $updates['status'] = 'Pendiente';
        }

        $report->update($updates);
    }

    public function updateReportStatus($reportId, $newStatus)
    {
        $report = EventReport::findOrFail($reportId);
        $updates = ['status' => $newStatus];

        if ($newStatus === 'En Atención' && !$report->dispatched_at) {
            $updates['dispatched_at'] = now();
        } elseif ($newStatus === 'Resuelto') {
            $updates['resolved_at'] = now();
        }

        $report->update($updates);
    }

    public function render()
    {
        $events = OperationalEvent::orderBy('is_active', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $currentEvent = $this->selectedEventId
            ? OperationalEvent::find($this->selectedEventId)
            : null;

        $categoryCounts = [];
        $statusCounts = ['total' => 0, 'Pendiente' => 0, 'En Atención' => 0, 'Resuelto' => 0];
        $reports = collect();

        $dynamicCategories = $this->getDynamicCategories();

        if ($currentEvent) {
            $allEventReports = EventReport::where('operational_event_id', $currentEvent->id)->get(['category', 'status']);
            $statusCounts['total'] = $allEventReports->count();
            $statusCounts['Pendiente'] = $allEventReports->where('status', 'Pendiente')->count();
            $statusCounts['En Atención'] = $allEventReports->where('status', 'En Atención')->count();
            $statusCounts['Resuelto'] = $allEventReports->where('status', 'Resuelto')->count();

            foreach (array_keys($dynamicCategories) as $catName) {
                $categoryCounts[$catName] = $allEventReports->where('category', $catName)->count();
            }

            $reports = EventReport::with(['sector', 'device', 'user'])
                ->where('operational_event_id', $currentEvent->id)
                ->when($this->filterCategory, fn($q) => $q->where('category', $this->filterCategory))
                ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
                ->when($this->filterSector, fn($q) => $q->where('sector_id', $this->filterSector))
                ->when($this->search, function ($q) {
                    $q->where(function ($sub) {
                        $sub->where('exact_address', 'like', '%' . $this->search . '%')
                            ->orWhere('caller_name', 'like', '%' . $this->search . '%')
                            ->orWhere('caller_phone', 'like', '%' . $this->search . '%')
                            ->orWhere('notes', 'like', '%' . $this->search . '%');
                    });
                })
                ->orderByRaw("CASE WHEN status = 'Pendiente' THEN 1 WHEN status = 'En Atención' THEN 2 ELSE 3 END")
                ->orderByRaw("CASE WHEN urgency = 'Alta' THEN 1 WHEN urgency = 'Media' THEN 2 ELSE 3 END")
                ->orderBy('created_at', 'desc')
                ->paginate(15);
        }

        return view('livewire.manage-operational-events', [
            'events' => $events,
            'currentEvent' => $currentEvent,
            'categories' => $dynamicCategories,
            'categoryCounts' => $categoryCounts,
            'statusCounts' => $statusCounts,
            'reports' => $reports,
            'sectors' => Sector::orderBy('name')->get(),
            'devices' => Device::orderBy('name')->get(),
        ])->layout('layouts.app');
    }
}
