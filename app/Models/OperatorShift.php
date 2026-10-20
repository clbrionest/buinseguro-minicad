<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperatorShift extends Model
{
    protected $fillable = [
        'shift_date',
        'shift_type',
        'opened_by_user_id',
        'closed_by_user_id',
        'received_by_user_id',
        'operators_on_duty',
        'equipment_status',
        'opening_notes',
        'shift_log_notes',
        'handover_notes',
        'stats_snapshot',
        'started_at',
        'closed_at',
        'handover_acknowledged_at',
        'is_active',
        'email_sent',
    ];

    protected $casts = [
        'shift_date' => 'date',
        'operators_on_duty' => 'array',
        'equipment_status' => 'array',
        'stats_snapshot' => 'array',
        'started_at' => 'datetime',
        'closed_at' => 'datetime',
        'handover_acknowledged_at' => 'datetime',
        'is_active' => 'boolean',
        'email_sent' => 'boolean',
    ];

    public function openedBy()
    {
        return $this->belongsTo(User::class, 'opened_by_user_id');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by_user_id');
    }

    /**
     * Dotación estructurada del turno de operadores (Operadores Telefónicos, Operadores de Cámaras CCTV, etc.)
     */
    public function crew()
    {
        return $this->belongsToMany(User::class, 'operator_shift_user')
            ->withPivot('station_role', 'console_station', 'time_exception')
            ->withTimestamps();
    }

    /**
     * Calcula las estadísticas operativas de la ventana horaria del turno
     */
    public static function compileShiftStatistics($startTime, $endTime = null): array
    {
        $endTime = $endTime ?? now();

        // 1. Procedimientos creados dentro del turno
        $shiftProcedures = Procedure::with(['sector', 'procedureType', 'device'])
            ->whereBetween('created_at', [$startTime, $endTime])
            ->get();

        $totalCreated = $shiftProcedures->count();
        $closedInShift = $shiftProcedures->where('status', 'Cerrado')->count();
        $preventiveCount = $shiftProcedures->where('is_preventive', true)->count();

        // 2. Procedimientos que quedan abiertos / pendientes al cierre para traspaso
        $pendingHandoverCases = Procedure::with(['sector', 'procedureType', 'device'])
            ->whereNotIn('status', ['Cerrado', 'Cancelado', 'Vinculado'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'time' => $p->created_at->format('d/m H:i'),
                'type' => $p->procedureType->name ?? 'N/A',
                'sector' => $p->sector->name ?? 'N/A',
                'address' => $p->exact_address,
                'status' => $p->status,
                'device' => $p->device->name ?? 'Sin móvil',
            ])->toArray();

        // 3. Tiempos SLA promedio en el turno
        $dispatched = $shiftProcedures->filter(fn($p) => !is_null($p->dispatch_minutes));
        $arrived = $shiftProcedures->filter(fn($p) => !is_null($p->arrived_at) && !is_null($p->arrival_minutes));

        $avgDispatchMinutes = $dispatched->count() > 0 ? round($dispatched->avg('dispatch_minutes'), 1) : null;
        $avgArrivalMinutes = $arrived->count() > 0 ? round($arrived->avg('arrival_minutes'), 1) : null;

        // 4. Desglose por Sector
        $bySector = $shiftProcedures
            ->groupBy(fn($p) => $p->sector->name ?? 'Sin Sector')
            ->map(fn($group) => $group->count())
            ->sortDesc()
            ->toArray();

        // 5. Desglose por Tipo de Procedimiento (Top 6)
        $byType = $shiftProcedures
            ->groupBy(fn($p) => $p->procedureType->name ?? 'Sin Tipo')
            ->map(fn($group) => $group->count())
            ->sortDesc()
            ->take(6)
            ->toArray();

        // 6. Llamadas PBX recibidas en el turno
        $shiftCalls = Call::whereBetween('created_at', [$startTime, $endTime])->get();
        $totalCalls = $shiftCalls->count();
        $prankCalls = $shiftCalls->where('classification', 'Pitanza / Broma')->count();
        $infoCalls = $shiftCalls->where('classification', 'Consulta / Información')->count();

        // 7. Patrullas / Móviles activos
        $activePatrolsCount = PatrolAssignment::where('is_active', true)->count();

        return [
            'period_start' => $startTime->format('d/m/Y H:i'),
            'period_end' => $endTime->format('d/m/Y H:i'),
            'total_procedures' => $totalCreated,
            'total_created' => $totalCreated,
            'closed_procedures' => $closedInShift,
            'closed_in_shift' => $closedInShift,
            'preventive_procedures' => $preventiveCount,
            'open_handover_count' => count($pendingHandoverCases),
            'pending_handover_count' => count($pendingHandoverCases),
            'open_handover_cases' => $pendingHandoverCases,
            'avg_dispatch_minutes' => $avgDispatchMinutes,
            'avg_arrival_minutes' => $avgArrivalMinutes,
            'by_sector' => $bySector,
            'by_type' => $byType,
            'total_calls' => $totalCalls,
            'calls_total' => $totalCalls,
            'prank_calls' => $prankCalls,
            'info_calls' => $infoCalls,
            'active_patrols_count' => $activePatrolsCount,
        ];
    }
}
