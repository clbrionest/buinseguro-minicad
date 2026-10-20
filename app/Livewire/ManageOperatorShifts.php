<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\OperatorShift;
use App\Models\ShiftReportRecipient;
use App\Models\SystemCatalogItem;
use App\Models\User;
use App\Models\AuditLog;
use App\Mail\ShiftClosedReportMail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class ManageOperatorShifts extends Component
{
    use WithPagination;

    // Filtros y Planificación de Pauta Diaria (Igual que Patrullas)
    public $filterDate;
    public $shift_type = 'Turno 1 (08:00 a 14:00)';
    public $editingShiftId = null;

    // Dotación estructurada del turno (Operadores Telefónicos, Operadores de Cámaras CCTV, etc.)
    public $crewMembers = [];
    public $selected_operators = [];
    public $opening_notes = '';
    public $equipment_status = [
        'radio_vhf' => 'Operativo',
        'central_pbx' => 'Operativo',
        'camaras_cctv' => 'Operativo',
        'gps_flota' => 'Operativo',
    ];

    // Formulario de Bitácora de Sala, Traspaso y Cierre del Turno Activo
    public $shift_log_notes = '';
    public $handover_notes = '';
    public $received_by_user_id = '';

    // Gestión rápida de Directores / Destinatarios de Email
    public $showRecipientsModal = false;
    public $recipient_name = '';
    public $recipient_email = '';
    public $recipient_role = '';

    // Mantenedor rápido de Equipos en Sala (con Soft-Delete)
    public $showEquipmentsModal = false;
    public $new_equipment_name = '';

    public function mount()
    {
        $this->filterDate = Carbon::now()->format('Y-m-d');
        $this->syncRoomEquipmentsDefaults();

        $blocks = $this->getShiftBlocks();
        $hour = (int) now()->format('G');
        if ($hour >= 8 && $hour < 14) {
            $this->shift_type = $blocks[0] ?? 'Turno 1 (08:00 a 14:00)';
        } elseif ($hour >= 14 && $hour < 20) {
            $this->shift_type = $blocks[1] ?? ($blocks[0] ?? 'Turno 2 (14:00 a 20:00)');
        } else {
            $this->shift_type = $blocks[2] ?? ($blocks[0] ?? 'Turno 3 (20:00 a 08:00)');
        }

        if (Auth::user()) {
            $this->selected_operators = [Auth::user()->name];
            $this->crewMembers = [
                [
                    'user_id' => Auth::id(),
                    'station_role' => 'Operador Telefónico',
                    'console_station' => Auth::user()->extension ? 'Anexo ' . Auth::user()->extension : 'Consola 1',
                    'time_exception' => '',
                ],
            ];
        }

        $activeShift = OperatorShift::where('is_active', true)->latest()->first();
        if ($activeShift) {
            $this->shift_log_notes = $activeShift->shift_log_notes ?? '';
            $this->handover_notes = $activeShift->handover_notes ?? '';
            if (is_array($activeShift->equipment_status)) {
                foreach ($this->equipment_status as $eqKey => $defaultVal) {
                    if (isset($activeShift->equipment_status[$eqKey])) {
                        $this->equipment_status[$eqKey] = $activeShift->equipment_status[$eqKey];
                    }
                }
            }
        }
    }

    public function syncRoomEquipmentsDefaults(): void
    {
        $map = SystemCatalogItem::roomEquipmentsMap();
        $updated = [];
        foreach ($map as $eqKey => $eqLabel) {
            $updated[$eqKey] = $this->equipment_status[$eqKey] ?? 'Operativo';
        }
        $this->equipment_status = $updated;
    }

    public function getShiftBlocks(): array
    {
        return SystemCatalogItem::namesFor('operator_shift_blocks', [
            'Turno 1 (08:00 a 14:00)',
            'Turno 2 (14:00 a 20:00)',
            'Turno 3 (20:00 a 08:00)',
        ]);
    }

    public function getStationRoles(): array
    {
        return SystemCatalogItem::namesFor('operator_station_roles', [
            'Operador Telefónico',
            'Operador de Cámaras (CCTV)',
            'Despachador Radial',
            'Jefe de Sala / Supervisor',
        ]);
    }

    public function getCurrentBlockQuota(): array
    {
        try {
            $item = SystemCatalogItem::where('catalog_group', 'operator_shift_blocks')
                ->where('name', $this->shift_type)
                ->first();

            if ($item && is_array($item->meta)) {
                return [
                    'start_time' => $item->meta['start_time'] ?? '08:00',
                    'end_time' => $item->meta['end_time'] ?? '14:00',
                    'min_phone_ops' => (int) ($item->meta['min_phone_ops'] ?? 2),
                    'min_camera_ops' => (int) ($item->meta['min_camera_ops'] ?? 4),
                ];
            }
        } catch (\Throwable $e) {
            // Fallback
        }

        $isNight = str_contains($this->shift_type, '20:00');
        return [
            'start_time' => str_contains($this->shift_type, '14:00') ? '14:00' : ($isNight ? '20:00' : '08:00'),
            'end_time' => str_contains($this->shift_type, '14:00') && !str_contains($this->shift_type, '08:00') ? '20:00' : ($isNight ? '08:00' : '14:00'),
            'min_phone_ops' => 2,
            'min_camera_ops' => $isNight ? 5 : 4,
        ];
    }

    public function addCrewMember($defaultRole = null)
    {
        $roles = $this->getStationRoles();
        if (!$defaultRole) {
            $phoneCount = collect($this->crewMembers)->where('station_role', 'Operador Telefónico')->count();
            $defaultRole = $phoneCount < 2 ? ($roles[0] ?? 'Operador Telefónico') : ($roles[1] ?? 'Operador de Cámaras (CCTV)');
        }

        $this->crewMembers[] = [
            'user_id' => '',
            'station_role' => $defaultRole,
            'console_station' => '',
            'time_exception' => '',
        ];
    }

    public function removeCrewMember($index)
    {
        if (isset($this->crewMembers[$index])) {
            unset($this->crewMembers[$index]);
            $this->crewMembers = array_values($this->crewMembers);
        }
    }

    /**
     * Genera automáticamente la plantilla de puestos sugerida para el bloque (ej. 2 Operadores Telefónicos + 4 o 5 de Cámaras)
     */
    public function applySuggestedTemplate()
    {
        $quota = $this->getCurrentBlockQuota();
        $phoneNeeded = $quota['min_phone_ops'] ?? 2;
        $cameraNeeded = $quota['min_camera_ops'] ?? 4;

        $rows = [];
        for ($i = 1; $i <= $phoneNeeded; $i++) {
            $rows[] = [
                'user_id' => ($i === 1 && Auth::id()) ? Auth::id() : '',
                'station_role' => 'Operador Telefónico',
                'console_station' => "Línea / Anexo {$i}",
                'time_exception' => '',
            ];
        }

        for ($j = 1; $j <= $cameraNeeded; $j++) {
            $rows[] = [
                'user_id' => '',
                'station_role' => 'Operador de Cámaras (CCTV)',
                'console_station' => "Muro CCTV {$j}",
                'time_exception' => '',
            ];
        }

        $this->crewMembers = $rows;
        session()->flash('message', "Plantilla cargada: {$phoneNeeded} Operadores Telefónicos y {$cameraNeeded} Operadores de Cámaras listos para asignar.");
    }

    /**
     * Rota / Clona la dotación del último turno armado para facilitar turnos rotativos
     */
    public function loadPreviousShiftCrew()
    {
        $prevShift = OperatorShift::with('crew')
            ->whereHas('crew')
            ->latest('id')
            ->first();

        if (!$prevShift || $prevShift->crew->isEmpty()) {
            session()->flash('error', 'No se encontró un turno anterior con dotación estructurada para clonar.');
            return;
        }

        $rows = [];
        foreach ($prevShift->crew as $member) {
            $rows[] = [
                'user_id' => $member->id,
                'station_role' => $member->pivot->station_role,
                'console_station' => $member->pivot->console_station ?? '',
                'time_exception' => $member->pivot->time_exception ?? '',
            ];
        }

        $this->crewMembers = $rows;
        session()->flash('message', "Dotación clonada desde {$prevShift->shift_type} para rotación rápida.");
    }

    /**
     * Guarda / Arma un turno de operadores en la pauta (igual que ManagePatrols::savePatrol)
     */
    public function saveShiftRoster($activateNow = false)
    {
        // Filtrar filas donde se haya seleccionado un usuario
        $filledCrew = array_values(array_filter($this->crewMembers, fn($m) => !empty($m['user_id'])));

        $this->validate([
            'filterDate' => 'required|date',
            'shift_type' => 'required|string|max:120',
            'opening_notes' => 'nullable|string|max:1000',
        ]);

        // Validar que no haya un mismo operador duplicado en el mismo turno
        $userIds = array_column($filledCrew, 'user_id');
        if (count($userIds) !== count(array_unique($userIds))) {
            $this->addError('crewMembers', 'Un mismo operador no puede ser asignado dos veces en el mismo turno.');
            return;
        }

        $operatorNames = [];
        $crewSync = [];

        if (!empty($filledCrew)) {
            $usersById = User::whereIn('id', $userIds)->get()->keyBy('id');
            foreach ($filledCrew as $member) {
                $uid = (int) $member['user_id'];
                if ($usersById->has($uid)) {
                    $u = $usersById[$uid];
                    $roleLabel = $member['station_role'] ?? 'Operador Telefónico';
                    $operatorNames[] = "{$u->name} ({$roleLabel})";
                    $crewSync[$uid] = [
                        'station_role' => $roleLabel,
                        'console_station' => $member['console_station'] ?? null,
                        'time_exception' => $member['time_exception'] ?? null,
                    ];
                }
            }
        }

        if (empty($operatorNames)) {
            $ops = array_values(array_filter($this->selected_operators));
            $operatorNames = !empty($ops) ? $ops : [Auth::user()->name];
        }

        if ($activateNow) {
            // Si había un turno previo cerrado pendiente de acuse de recibo, lo marcamos como recepcionado
            $lastClosed = OperatorShift::where('is_active', false)
                ->whereNull('handover_acknowledged_at')
                ->latest('closed_at')
                ->first();

            if ($lastClosed) {
                $lastClosed->update([
                    'handover_acknowledged_at' => now(),
                    'received_by_user_id' => $lastClosed->received_by_user_id ?: Auth::id(),
                ]);
            }

            OperatorShift::where('is_active', true)->update([
                'is_active' => false,
                'closed_at' => now(),
                'closed_by_user_id' => Auth::id(),
            ]);
        }

        if ($this->editingShiftId) {
            $shift = OperatorShift::findOrFail($this->editingShiftId);
            $shift->update([
                'shift_date' => $this->filterDate,
                'shift_type' => $this->shift_type,
                'operators_on_duty' => $operatorNames,
                'equipment_status' => $this->equipment_status,
                'opening_notes' => $this->opening_notes,
                'is_active' => $activateNow ? true : $shift->is_active,
            ]);
        } else {
            $shift = OperatorShift::create([
                'shift_date' => $this->filterDate,
                'shift_type' => $this->shift_type,
                'opened_by_user_id' => Auth::id(),
                'operators_on_duty' => $operatorNames,
                'equipment_status' => $this->equipment_status,
                'opening_notes' => $this->opening_notes,
                'started_at' => now(),
                'is_active' => (bool) $activateNow,
            ]);
        }

        if (!empty($crewSync)) {
            $shift->crew()->sync($crewSync);
        }

        AuditLog::record(
            $activateNow ? 'shift.opened' : 'shift.planned',
            'Turnos de Sala',
            ($activateNow ? 'Apertura de Turno de Sala' : 'Armado de Pauta de Turno de Sala') . " #{$shift->id} ({$shift->shift_type}) para fecha {$this->filterDate}.",
            $shift,
            ['operators_on_duty' => $operatorNames, 'equipment_status' => $this->equipment_status]
        );

        $this->editingShiftId = null;
        $this->opening_notes = '';

        session()->flash(
            'message',
            $activateNow
                ? "Turno #{$shift->id} ({$shift->shift_type}) iniciado y activo en Sala CAD."
                : "Pauta del {$shift->shift_type} ({$this->filterDate}) armada y guardada correctamente."
        );
    }

    /**
     * Inicia un turno inmediatamente (compatibilidad directa y botón de apertura rápida)
     */
    public function openNewShift()
    {
        $this->saveShiftRoster(true);
    }

    public function editShiftRoster($shiftId)
    {
        $shift = OperatorShift::with('crew')->findOrFail($shiftId);
        $this->editingShiftId = $shift->id;
        $this->filterDate = $shift->shift_date ? $shift->shift_date->format('Y-m-d') : $this->filterDate;
        $this->shift_type = $shift->shift_type;
        $this->opening_notes = $shift->opening_notes ?? '';

        if ($shift->crew->count() > 0) {
            $rows = [];
            foreach ($shift->crew as $member) {
                $rows[] = [
                    'user_id' => $member->id,
                    'station_role' => $member->pivot->station_role,
                    'console_station' => $member->pivot->console_station ?? '',
                    'time_exception' => $member->pivot->time_exception ?? '',
                ];
            }
            $this->crewMembers = $rows;
        }
    }

    public function activatePlannedShift($shiftId)
    {
        $shift = OperatorShift::findOrFail($shiftId);

        OperatorShift::where('is_active', true)->update([
            'is_active' => false,
            'closed_at' => now(),
            'closed_by_user_id' => Auth::id(),
        ]);

        $shift->update([
            'is_active' => true,
            'started_at' => now(),
            'closed_at' => null,
        ]);

        AuditLog::record(
            'shift.opened',
            'Turnos de Sala',
            "Activación de Turno de Sala #{$shift->id} ({$shift->shift_type}).",
            $shift
        );

        session()->flash('message', "Turno #{$shift->id} ({$shift->shift_type}) activado como guardia en curso.");
    }

    public function deleteShiftRoster($shiftId)
    {
        abort_unless(Auth::user()?->hasAnyRole(['Administrador', 'Supervisor']), 403);
        $shift = OperatorShift::findOrFail($shiftId);
        if ($shift->is_active) {
            session()->flash('error', 'No puedes eliminar un turno que está actualmente abierto en sala. Ciérralo primero.');
            return;
        }
        $shift->delete();
        session()->flash('message', 'Pauta de turno eliminada.');
    }

    public function saveShiftProgress()
    {
        $activeShift = OperatorShift::where('is_active', true)->latest()->first();
        if (!$activeShift) {
            return;
        }

        $activeShift->update([
            'shift_log_notes' => $this->shift_log_notes,
            'handover_notes' => $this->handover_notes,
            'equipment_status' => $this->equipment_status,
        ]);

        session()->flash('message', 'Bitácora de sala y consignas de traspaso guardadas.');
    }

    public function closeAndHandoverShift()
    {
        $activeShift = OperatorShift::where('is_active', true)->latest()->first();
        if (!$activeShift) {
            return;
        }

        $closedAt = now();
        $stats = OperatorShift::compileShiftStatistics($activeShift->started_at, $closedAt);

        $activeShift->update([
            'closed_by_user_id' => Auth::id(),
            'received_by_user_id' => $this->received_by_user_id ?: null,
            'shift_log_notes' => $this->shift_log_notes,
            'handover_notes' => $this->handover_notes,
            'equipment_status' => $this->equipment_status,
            'stats_snapshot' => $stats,
            'closed_at' => $closedAt,
            'is_active' => false,
        ]);

        $emailStatus = $this->dispatchDirectorEmails($activeShift);

        AuditLog::record(
            'shift.closed',
            'Turnos de Sala',
            "Cierre y Entrega de Turno de Sala #{$activeShift->id} ({$activeShift->shift_type}). {$emailStatus}",
            $activeShift,
            [
                'received_by_user_id' => $activeShift->received_by_user_id,
                'total_created' => $stats['total_created'] ?? 0,
                'pending_handover_count' => $stats['pending_handover_count'] ?? 0,
            ]
        );

        session()->flash('message', "Turno #{$activeShift->id} cerrado y acta de traspaso generada. {$emailStatus}");
    }

    public function resendShiftEmail($shiftId)
    {
        abort_unless(Auth::user()?->hasAnyRole(['Administrador', 'Supervisor', 'Operador']), 403);
        $shift = OperatorShift::findOrFail($shiftId);
        $status = $this->dispatchDirectorEmails($shift);
        session()->flash('message', "Reenvío de reporte Turno #{$shift->id}: {$status}");
    }

    protected function dispatchDirectorEmails(OperatorShift $shift): string
    {
        $recipients = ShiftReportRecipient::where('is_active', true)->pluck('email')->filter()->toArray();
        if (empty($recipients)) {
            return 'No hay destinatarios activos configurados para el envío de email.';
        }

        try {
            Mail::to($recipients)->send(new ShiftClosedReportMail($shift));
            $shift->update(['email_sent' => true]);
            return 'Reporte estadístico enviado por correo a ' . count($recipients) . ' director(es)/jefatura(s).';
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Error enviando correo de cierre de turno: ' . $e->getMessage());
            return 'El turno se cerró correctamente, pero el servidor SMTP reportó un aviso (' . $e->getMessage() . '). Puedes revisar la plantilla de correo en el historial.';
        }
    }

    public function addRecipient()
    {
        abort_unless(Auth::user()?->hasAnyRole(['Administrador', 'Supervisor']), 403);

        $this->validate([
            'recipient_name' => 'required|string|max:120',
            'recipient_email' => 'required|email|max:150',
            'recipient_role' => 'nullable|string|max:100',
        ]);

        $rec = ShiftReportRecipient::create([
            'name' => $this->recipient_name,
            'email' => $this->recipient_email,
            'role_title' => $this->recipient_role ?: 'Dirección / Jefatura',
            'is_active' => true,
        ]);

        AuditLog::record(
            'recipient.created',
            'Turnos de Sala',
            "Se agregó destinatario de reporte de turno: {$rec->name} ({$rec->email}).",
            $rec
        );

        $this->reset(['recipient_name', 'recipient_email', 'recipient_role']);
        session()->flash('recipient_message', 'Destinatario agregado a la nómina de cierre de turno.');
    }

    public function toggleRecipient($id)
    {
        abort_unless(Auth::user()?->hasAnyRole(['Administrador', 'Supervisor']), 403);

        $rec = ShiftReportRecipient::find($id);
        if ($rec) {
            $rec->update(['is_active' => !$rec->is_active]);
        }
    }

    public function deleteRecipient($id)
    {
        abort_unless(Auth::user()?->hasAnyRole(['Administrador', 'Supervisor']), 403);

        ShiftReportRecipient::destroy($id);
    }

    /**
     * Agrega un nuevo Equipo en Sala (o lo restaura si estaba en Soft-Delete)
     */
    public function addRoomEquipment()
    {
        abort_unless(Auth::user()?->hasAnyRole(['Administrador', 'Supervisor']), 403);

        $this->new_equipment_name = trim($this->new_equipment_name);
        $this->validate([
            'new_equipment_name' => 'required|string|min:2|max:120',
        ], [
            'new_equipment_name.required' => 'Ingrese el nombre del equipo en sala.',
        ]);

        $codeKey = strtolower(\Illuminate\Support\Str::slug($this->new_equipment_name, '_'));

        $existingActive = SystemCatalogItem::where('catalog_group', 'room_equipments')
            ->where(function ($q) use ($codeKey) {
                $q->whereRaw('LOWER(name) = ?', [mb_strtolower($this->new_equipment_name)])
                  ->orWhere('code', $codeKey);
            })
            ->first();

        if ($existingActive) {
            $this->addError('new_equipment_name', 'Este equipo ya se encuentra activo en la sala.');
            return;
        }

        $trashed = SystemCatalogItem::onlyTrashed()
            ->where('catalog_group', 'room_equipments')
            ->where(function ($q) use ($codeKey) {
                $q->whereRaw('LOWER(name) = ?', [mb_strtolower($this->new_equipment_name)])
                  ->orWhere('code', $codeKey);
            })
            ->first();

        if ($trashed) {
            $trashed->restore();
            $trashed->update([
                'name' => $this->new_equipment_name,
                'code' => $codeKey,
                'is_active' => true,
            ]);
            $eqItem = $trashed;
        } else {
            $maxOrder = (int) SystemCatalogItem::where('catalog_group', 'room_equipments')->max('sort_order');
            $eqItem = SystemCatalogItem::create([
                'catalog_group' => 'room_equipments',
                'name' => $this->new_equipment_name,
                'code' => $codeKey,
                'icon' => 'fa-server',
                'color' => 'blue',
                'sort_order' => $maxOrder + 1,
                'is_active' => true,
            ]);
        }

        $this->equipment_status[$codeKey] = 'Operativo';
        $this->new_equipment_name = '';

        AuditLog::record(
            'equipment.added',
            'Turnos de Sala',
            "Equipo en sala agregado/habilitado para apertura y cierre de turno: {$eqItem->name}.",
            $eqItem
        );

        session()->flash('equipment_message', "Equipo «{$eqItem->name}» agregado para verificación en apertura y cierre de turno.");
    }

    /**
     * Quita (Soft-Delete) un Equipo en Sala para que no aparezca en futuras aperturas/cierres sin perder historial
     */
    public function softDeleteRoomEquipment($id)
    {
        abort_unless(Auth::user()?->hasAnyRole(['Administrador', 'Supervisor']), 403);

        $item = SystemCatalogItem::where('catalog_group', 'room_equipments')->find($id);
        if ($item) {
            $codeKey = strtolower($item->code ?: \Illuminate\Support\Str::slug($item->name, '_'));
            $itemName = $item->name;
            $item->delete(); // Soft-Delete

            unset($this->equipment_status[$codeKey]);

            AuditLog::record(
                'equipment.soft_deleted',
                'Turnos de Sala',
                "Equipo en sala dado de baja (Soft-Delete): {$itemName}."
            );

            session()->flash('equipment_message', "Equipo «{$itemName}» quitado (Soft-Delete). Puedes restaurarlo cuando lo necesites.");
        }
    }

    /**
     * Restaura un Equipo en Sala previamente quitado con Soft-Delete
     */
    public function restoreRoomEquipment($id)
    {
        abort_unless(Auth::user()?->hasAnyRole(['Administrador', 'Supervisor']), 403);

        $item = SystemCatalogItem::onlyTrashed()
            ->where('catalog_group', 'room_equipments')
            ->find($id);

        if ($item) {
            $item->restore();
            $item->update(['is_active' => true]);
            $codeKey = strtolower($item->code ?: \Illuminate\Support\Str::slug($item->name, '_'));
            $this->equipment_status[$codeKey] = 'Operativo';

            AuditLog::record(
                'equipment.restored',
                'Turnos de Sala',
                "Equipo en sala restaurado desde Soft-Delete: {$item->name}.",
                $item
            );

            session()->flash('equipment_message', "Equipo «{$item->name}» restaurado y activo para apertura/cierre de turno.");
        }
    }

    public function render()
    {
        $activeShift = OperatorShift::with(['openedBy', 'receivedBy', 'crew'])
            ->where('is_active', true)
            ->latest()
            ->first();

        $lastClosedShift = OperatorShift::with(['openedBy', 'closedBy', 'receivedBy', 'crew'])
            ->where('is_active', false)
            ->whereNotNull('closed_at')
            ->latest('closed_at')
            ->first();

        $liveStats = $activeShift
            ? OperatorShift::compileShiftStatistics($activeShift->started_at, now())
            : null;

        // Turnos armados para la fecha seleccionada en el filtro superior (igual que ManagePatrols)
        $shiftsOfDate = OperatorShift::with(['openedBy', 'closedBy', 'receivedBy', 'crew'])
            ->where(function ($q) {
                $q->whereDate('shift_date', $this->filterDate)
                  ->orWhereDate('started_at', $this->filterDate);
            })
            ->orderByDesc('is_active')
            ->orderBy('id')
            ->get();

        $pastShifts = OperatorShift::with(['openedBy', 'closedBy', 'receivedBy', 'crew'])
            ->where('is_active', false)
            ->orderByDesc('closed_at')
            ->orderByDesc('id')
            ->paginate(10);

        $operatorsList = User::orderBy('name')->get();
        $recipients = ShiftReportRecipient::orderBy('name')->get();

        $roomEquipmentsMap = SystemCatalogItem::roomEquipmentsMap();
        $roomEquipmentItems = SystemCatalogItem::where('catalog_group', 'room_equipments')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $trashedRoomEquipments = SystemCatalogItem::onlyTrashed()
            ->where('catalog_group', 'room_equipments')
            ->orderByDesc('deleted_at')
            ->get();

        // Contadores de dotación en el formulario actual
        $filledCrew = collect($this->crewMembers)->filter(fn($m) => !empty($m['user_id']));
        $assignedPhoneOps = $filledCrew->filter(fn($m) => str_contains(strtolower($m['station_role'] ?? ''), 'telef'))->count();
        $assignedCameraOps = $filledCrew->filter(fn($m) => str_contains(strtolower($m['station_role'] ?? ''), 'cámara') || str_contains(strtolower($m['station_role'] ?? ''), 'camara') || str_contains(strtolower($m['station_role'] ?? ''), 'cctv'))->count();

        return view('livewire.manage-operator-shifts', [
            'activeShift' => $activeShift,
            'lastClosedShift' => $lastClosedShift,
            'liveStats' => $liveStats,
            'shiftsOfDate' => $shiftsOfDate,
            'pastShifts' => $pastShifts,
            'operatorsList' => $operatorsList,
            'recipients' => $recipients,
            'shiftBlocks' => $this->getShiftBlocks(),
            'stationRoles' => $this->getStationRoles(),
            'blockQuota' => $this->getCurrentBlockQuota(),
            'assignedPhoneOps' => $assignedPhoneOps,
            'assignedCameraOps' => $assignedCameraOps,
            'roomEquipmentsMap' => $roomEquipmentsMap,
            'roomEquipmentItems' => $roomEquipmentItems,
            'trashedRoomEquipments' => $trashedRoomEquipments,
        ])->layout('layouts.app');
    }
}
