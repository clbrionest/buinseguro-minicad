<?php

namespace App\Exports;

use App\Models\Procedure;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProceduresExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public $startDate;

    public $endDate;

    public $sector_id;

    public $procedure_type_id;

    public $device_id;

    public $status_filter;

    public $user_id;

    public function __construct(
        $startDate,
        $endDate,
        $sector_id = null,
        $procedure_type_id = null,
        $device_id = null,
        $status_filter = null,
        $user_id = null
    ) {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->sector_id = $sector_id;
        $this->procedure_type_id = $procedure_type_id;
        $this->device_id = $device_id;
        $this->status_filter = $status_filter;
        $this->user_id = $user_id;
    }

    public function query()
    {
        return Procedure::query()
            ->with(['sector', 'procedureType', 'initialProcedureType', 'device', 'user', 'origin', 'patrolAssignment.crew'])
            ->withCount(['logs', 'evidences'])
            ->when($this->startDate, fn ($q) => $q->whereDate('procedures.created_at', '>=', $this->startDate))
            ->when($this->endDate, fn ($q) => $q->whereDate('procedures.created_at', '<=', $this->endDate))
            ->when($this->sector_id, fn ($q) => $q->where('procedures.sector_id', $this->sector_id))
            ->when($this->procedure_type_id, fn ($q) => $q->where('procedures.procedure_type_id', $this->procedure_type_id))
            ->when($this->device_id, fn ($q) => $q->where('procedures.device_id', $this->device_id))
            ->when($this->status_filter, fn ($q) => $q->where('procedures.status', $this->status_filter))
            ->when($this->user_id, fn ($q) => $q->where('procedures.user_id', $this->user_id))
            ->orderBy('procedures.created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'ID Caso',
            'Fecha y Hora Inicio',
            'Fecha y Hora Cierre',
            'Sector',
            'Dirección',
            'Categoría Inicial (Llamado)',
            'Categoría Operativa Final',
            'Antecedentes / Metadatos',
            'Origen',
            'Solicitante',
            'Teléfono Solicitante',
            'Móvil Asignado',
            'Tripulación (Inspectores)',
            'Estado Final',
            'Derivado a Organismo',
            'Resolución de Cierre',
            'SLA Despacho (min)',
            'SLA Arribo al Lugar (min)',
            'Distancia GPS Móvil (m)',
            'Actuaciones en Bitácora',
            'Evidencias Adjuntas',
            'Operador / Registrado Por',
        ];
    }

    public function map($procedure): array
    {
        $crewText = 'Sin tripulación registrada';

        if ($procedure->patrolAssignment && $procedure->patrolAssignment->crew->count() > 0) {
            $crewMembers = [];
            foreach ($procedure->patrolAssignment->crew as $inspector) {
                $crewMembers[] = $inspector->name.' ('.$inspector->pivot->role.')';
            }
            $crewText = implode(', ', $crewMembers);
        }

        $metaText = collect($procedure->formatted_metadata)
            ->map(fn ($m) => "{$m['label']}: {$m['value']}")
            ->join(' | ') ?: 'S/A';

        return [
            $procedure->id,
            $procedure->created_at?->format('d/m/Y H:i:s'),
            $procedure->closed_at ? $procedure->closed_at->format('d/m/Y H:i:s') : 'En curso',
            $procedure->sector->name ?? 'N/A',
            $procedure->exact_address,
            $procedure->initialProcedureType->name ?? ($procedure->procedureType->name ?? 'N/A'),
            $procedure->procedureType->name ?? 'N/A',
            $metaText,
            $procedure->origin->name ?? 'N/A',
            $procedure->caller_name ?: 'Anónimo',
            $procedure->caller_phone ?: 'S/T',
            $procedure->device->name ?? 'Sin asignar',
            $crewText,
            $procedure->status,
            $procedure->derived_to ?: 'N/A',
            $procedure->closure_resolution ?: 'N/A',
            $procedure->dispatched_at ? $procedure->dispatch_minutes : 'N/A',
            $procedure->arrived_at ? $procedure->arrival_minutes : 'N/A',
            ! is_null($procedure->mobile_arrival_distance) ? $procedure->mobile_arrival_distance : 'N/A',
            $procedure->logs_count ?? 0,
            $procedure->evidences_count ?? 0,
            $procedure->user->name ?? 'Sistema',
        ];
    }
}
