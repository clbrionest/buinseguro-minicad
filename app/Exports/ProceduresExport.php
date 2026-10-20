<?php

namespace App\Exports;

use App\Models\Procedure;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProceduresExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    // Variables para recibir los filtros
    public $startDate;
    public $endDate;
    public $sector_id;
    public $procedure_type_id;

    // El constructor recibe los filtros cuando llamamos a esta clase
    public function __construct($startDate, $endDate, $sector_id, $procedure_type_id)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->sector_id = $sector_id;
        $this->procedure_type_id = $procedure_type_id;
    }

    public function query()
    {
        return Procedure::query()
            ->with(['sector', 'procedureType', 'initialProcedureType', 'device', 'user', 'origin', 'patrolAssignment.crew'])
            ->whereDate('procedures.created_at', '>=', $this->startDate)
            ->whereDate('procedures.created_at', '<=', $this->endDate)
            ->when($this->sector_id, fn($q) => $q->where('procedures.sector_id', $this->sector_id))
            ->when($this->procedure_type_id, fn($q) => $q->where('procedures.procedure_type_id', $this->procedure_type_id))
            ->orderBy('procedures.created_at', 'desc');
    }

    // 2. Los encabezados de la primera fila del Excel
    public function headings(): array
    {
        return [
            'ID Caso',
            'Fecha y Hora Inicio',
            'Sector',
            'Dirección',
            'Categoría Inicial (Llamado)',
            'Categoría Operativa Final',
            'Antecedentes / Metadatos',
            'Origen',
            'Móvil Asignado',
            'Tripulación (Inspectores)',
            'Estado Final',
            'SLA Despacho (min)',
            'SLA Arribo al Lugar (min)',
            'Distancia GPS Móvil (m)',
            'Registrado Por'
        ];
    }

    // 3. Mapear qué dato va en cada columna
    public function map($procedure): array
    {
        $crewText = 'Sin tripulación registrada';
        
        if ($procedure->patrolAssignment && $procedure->patrolAssignment->crew->count() > 0) {
            $crewMembers = [];
            foreach ($procedure->patrolAssignment->crew as $inspector) {
                $crewMembers[] = $inspector->name . ' (' . $inspector->pivot->role . ')';
            }
            $crewText = implode(', ', $crewMembers);
        }

        $metaText = collect($procedure->formatted_metadata)
            ->map(fn($m) => "{$m['label']}: {$m['value']}")
            ->join(' | ') ?: 'S/A';

        return [
            $procedure->id,
            $procedure->created_at->format('d/m/Y H:i:s'),
            $procedure->sector->name ?? 'N/A',
            $procedure->exact_address,
            $procedure->initialProcedureType->name ?? ($procedure->procedureType->name ?? 'N/A'),
            $procedure->procedureType->name ?? 'N/A',
            $metaText,
            $procedure->origin->name ?? 'N/A',
            $procedure->device->name ?? 'Sin asignar',
            $crewText,
            $procedure->status,
            $procedure->dispatched_at ? $procedure->dispatch_minutes : 'N/A',
            $procedure->arrived_at ? $procedure->arrival_minutes : 'N/A',
            !is_null($procedure->mobile_arrival_distance) ? $procedure->mobile_arrival_distance : 'N/A',
            $procedure->user->name ?? 'Sistema'
        ];
    }
}