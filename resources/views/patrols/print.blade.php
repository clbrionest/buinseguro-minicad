<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informe Patrullaje Preventivo {{ $patrol->folio }} - BuinSeguro</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .print-sheet { box-shadow: none !important; border: none !important; margin: 0 !important; padding: 0 !important; max-width: 100% !important; }
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-900 font-sans py-8 px-4">

    {{-- BARRA SUPERIOR DE IMPRESIÓN (NO SE IMPRIME) --}}
    <div class="no-print max-w-4xl mx-auto mb-4 flex justify-between items-center bg-gray-900 text-white p-3 rounded-lg shadow">
        <div class="flex items-center gap-3">
            <a href="{{ route('patrols.preventive.show', $patrol) }}" class="text-xs font-bold text-gray-300 hover:text-white">
                ← Volver al Patrullaje {{ $patrol->folio }}
            </a>
            <span class="text-gray-600">|</span>
            <span class="text-xs text-gray-300">Informe Oficial de Patrullaje Preventivo</span>
        </div>
        <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-black text-xs py-2 px-4 rounded shadow flex items-center gap-1.5">
            🖨️ Imprimir / Guardar como PDF
        </button>
    </div>

    {{-- HOJA DE FICHA OFICIAL --}}
    <div class="print-sheet max-w-4xl mx-auto bg-white shadow-lg border border-gray-300 rounded-lg p-8">
        
        {{-- ENCABEZADO INSTITUCIONAL --}}
        <div class="flex justify-between items-start border-b-2 border-gray-800 pb-4 mb-6">
            <div>
                <p class="text-xs font-black uppercase tracking-widest text-gray-500">Ilustre Municipalidad de Buin</p>
                <h1 class="text-2xl font-black text-gray-900 uppercase">Dirección de Seguridad Pública — BuinSeguro</h1>
                <p class="text-sm font-semibold text-gray-600">Informe Operativo de Patrullaje Preventivo</p>
            </div>
            <div class="text-right">
                <div class="inline-block bg-gray-900 text-white px-4 py-1.5 rounded font-black text-lg">
                    FOLIO: {{ $patrol->folio }}
                </div>
                <p class="text-xs text-gray-500 mt-1 font-mono">
                    Emitido: {{ now()->format('d/m/Y H:i:s') }}
                </p>
                <p class="text-xs font-bold uppercase mt-0.5">
                    Estado: <span class="{{ $patrol->status === 'Completado' ? 'text-blue-700' : 'text-emerald-700' }}">{{ $patrol->status }}</span>
                </p>
            </div>
        </div>

        {{-- SECCIÓN 1: TIEMPOS OPERATIVOS Y CONTROL KILOMETRAJE --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6 bg-gray-50 p-4 rounded border border-gray-200 text-xs">
            <div>
                <span class="block text-gray-500 uppercase font-bold text-[10px]">Hora Inicio</span>
                <span class="text-sm font-black text-gray-900 font-mono">{{ $patrol->started_at->format('d/m/Y H:i') }}</span>
            </div>
            <div>
                <span class="block text-gray-500 uppercase font-bold text-[10px]">Hora Fin</span>
                <span class="text-sm font-black text-gray-900 font-mono">
                    {{ $patrol->ended_at ? $patrol->ended_at->format('d/m/Y H:i') : 'En curso' }}
                </span>
            </div>
            <div>
                <span class="block text-gray-500 uppercase font-bold text-[10px]">Duración de Ronda</span>
                <span class="text-sm font-black text-gray-900 font-mono">{{ $patrol->duration_formatted }}</span>
            </div>
            <div>
                <span class="block text-gray-500 uppercase font-bold text-[10px]">Recorrido (Km)</span>
                <span class="text-sm font-black text-gray-900 font-mono">
                    @if($patrol->start_odometer && $patrol->end_odometer)
                        {{ number_format($patrol->end_odometer - $patrol->start_odometer, 0, ',', '.') }} km
                    @else
                        {{ $patrol->start_odometer ? 'Inicial: '.number_format($patrol->start_odometer, 0, ',', '.').' km' : 'N/R' }}
                    @endif
                </span>
            </div>
        </div>

        {{-- SECCIÓN 2: DATOS DEL MÓVIL, TRIPULACIÓN Y SECTOR --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 text-xs">
            <div class="border border-gray-300 rounded p-4">
                <h2 class="text-xs font-black uppercase tracking-wider text-gray-700 border-b pb-1.5 mb-2">1. Datos de la Unidad y Sector</h2>
                <div class="space-y-1.5">
                    <div><span class="font-bold text-gray-600">Móvil / Vehículo:</span> <strong class="text-gray-900">{{ $patrol->device?->name ?? 'Sin asignar' }}</strong></div>
                    <div><span class="font-bold text-gray-600">Sector Asignado:</span> {{ $patrol->sector?->name ?? 'Comuna General' }}</div>
                    <div><span class="font-bold text-gray-600">Tipo de Patrullaje:</span> <span class="font-bold">{{ $patrol->patrol_type }}</span></div>
                    <div><span class="font-bold text-gray-600">Operador Despachador:</span> {{ $patrol->user?->name ?? 'Central CAD' }}</div>
                </div>
            </div>

            <div class="border border-gray-300 rounded p-4">
                <h2 class="text-xs font-black uppercase tracking-wider text-gray-700 border-b pb-1.5 mb-2">2. Personal y Tripulación en Terreno</h2>
                <div class="space-y-1.5">
                    @if($patrol->patrolAssignment?->crew && $patrol->patrolAssignment->crew->count() > 0)
                        @foreach($patrol->patrolAssignment->crew as $crewMember)
                            <div class="flex justify-between items-center">
                                <span>{{ $crewMember->name }}</span>
                                <span class="font-mono text-gray-500 font-bold">({{ $crewMember->pivot->role ?? 'Inspector' }})</span>
                            </div>
                        @endforeach
                    @else
                        <div class="text-gray-500 italic">Sin tripulación registrada formalmente en el turno.</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- SECCIÓN 3: OBJETIVO Y CONSIGNA --}}
        @if($patrol->mission_objective)
            <div class="mb-6 p-3 bg-gray-50 rounded border border-gray-200 text-xs">
                <span class="font-black text-gray-700 uppercase block mb-1">Misión / Consigna Impartida:</span>
                <p class="text-gray-800 italic">"{{ $patrol->mission_objective }}"</p>
            </div>
        @endif

        {{-- SECCIÓN 4: TABLA OFICIAL DE PUNTOS E HITOS DE CONTROL --}}
        <div class="mb-6">
            <div class="flex justify-between items-center border-b border-gray-300 pb-1.5 mb-2">
                <h2 class="text-xs font-black uppercase tracking-wider text-gray-700">
                    3. Validación de Hitos y Puntos de Control Asignados
                </h2>
                <span class="text-xs font-black font-mono">
                    Cumplimiento: {{ $patrol->validated_checkpoints_count }} / {{ $patrol->total_checkpoints_count }} ({{ $patrol->validation_percentage }}%)
                </span>
            </div>

            <table class="w-full text-left text-xs border border-gray-300">
                <thead class="bg-gray-100 text-gray-700 uppercase text-[10px] font-black border-b border-gray-300">
                    <tr>
                        <th class="py-2 px-2 text-center w-8">#</th>
                        <th class="py-2 px-3">Hito / Punto de Control</th>
                        <th class="py-2 px-3">Ubicación / Dirección</th>
                        <th class="py-2 px-3 text-center">Hora Paso</th>
                        <th class="py-2 px-3 text-center">Distancia GPS</th>
                        <th class="py-2 px-3 text-center">Estado</th>
                        <th class="py-2 px-3">Validador</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($patrol->checkpoints as $cp)
                        <tr class="{{ $cp->status === 'Validado' ? 'bg-emerald-50/50' : '' }}">
                            <td class="py-2 px-2 text-center font-bold text-gray-500">{{ $cp->order }}</td>
                            <td class="py-2 px-3 font-bold text-gray-900">{{ $cp->name }}</td>
                            <td class="py-2 px-3 text-gray-600">{{ $cp->address ?: 'Ubicación comunal' }}</td>
                            <td class="py-2 px-3 text-center font-mono font-bold">
                                {{ $cp->validated_at ? $cp->validated_at->format('H:i:s') : '—' }}
                            </td>
                            <td class="py-2 px-3 text-center font-mono text-gray-700">
                                {{ !is_null($cp->validation_distance_meters) ? $cp->validation_distance_meters.' m' : '—' }}
                            </td>
                            <td class="py-2 px-3 text-center font-bold">
                                @if($cp->status === 'Validado')
                                    <span class="text-emerald-700">✓ Validado</span>
                                @elseif($cp->status === 'Omitido')
                                    <span class="text-gray-500">Omitido</span>
                                @else
                                    <span class="text-amber-600">Pendiente</span>
                                @endif
                            </td>
                            <td class="py-2 px-3 text-gray-700">{{ $cp->validatedBy?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-3 text-gray-500 italic">No se asignaron hitos específicos a este patrullaje.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- SECCIÓN 5: PROCEDIMIENTOS ORIGINADOS EN EL PATRULLAJE --}}
        <div class="mb-6">
            <h2 class="text-xs font-black uppercase tracking-wider text-gray-700 border-b pb-1.5 mb-2">
                4. Procedimientos Generados a Partir de este Patrullaje Preventivo ({{ $patrol->procedures->count() }})
            </h2>
            @if($patrol->procedures->count() > 0)
                <table class="w-full text-left text-xs border border-gray-300">
                    <thead class="bg-gray-100 text-gray-700 uppercase text-[10px] font-black border-b border-gray-300">
                        <tr>
                            <th class="py-2 px-3">Folio Caso</th>
                            <th class="py-2 px-3">Tipo de Procedimiento</th>
                            <th class="py-2 px-3">Dirección del Suceso</th>
                            <th class="py-2 px-3 text-center">Hora Inicio</th>
                            <th class="py-2 px-3 text-center">Estado Caso</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($patrol->procedures as $proc)
                            <tr>
                                <td class="py-2 px-3 font-mono font-black text-gray-900">#{{ $proc->id }}</td>
                                <td class="py-2 px-3 font-bold">{{ $proc->procedureType?->name ?? 'Procedimiento' }}</td>
                                <td class="py-2 px-3 text-gray-600">{{ $proc->exact_address }}</td>
                                <td class="py-2 px-3 text-center font-mono">{{ $proc->created_at->format('H:i:s') }}</td>
                                <td class="py-2 px-3 text-center font-bold">{{ $proc->status }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-xs text-gray-500 italic">No se registraron procedimientos derivados durante la realización de este patrullaje preventivo.</p>
            @endif
        </div>

        {{-- SECCIÓN 6: RESUMEN DE RUTA GPS --}}
        <div class="mb-6 p-3 bg-gray-50 rounded border border-gray-200 text-xs">
            <h2 class="text-xs font-black uppercase tracking-wider text-gray-700 mb-1">
                5. Trazabilidad de Ruta GPS
            </h2>
            <div class="flex justify-between items-center text-gray-600">
                <span>Waypoints satelitales registrados por el móvil: <strong>{{ count($patrol->gps_route ?: []) }} puntos</strong></span>
                <span>Intervalo de monitoreo: {{ $patrol->started_at->format('H:i') }} a {{ $patrol->ended_at ? $patrol->ended_at->format('H:i') : 'Presente' }}</span>
            </div>
        </div>

        {{-- SECCIÓN 7: OBSERVACIONES DE TÉRMINO --}}
        @if($patrol->completion_notes)
            <div class="mb-8 p-3 bg-gray-50 rounded border border-gray-200 text-xs">
                <span class="font-black text-gray-700 uppercase block mb-1">6. Novedades y Observaciones de Cierre:</span>
                <p class="text-gray-800 whitespace-pre-line">{{ $patrol->completion_notes }}</p>
            </div>
        @endif

        {{-- SECCIÓN DE FIRMAS --}}
        <div class="grid grid-cols-2 gap-12 pt-12 mt-8 border-t border-gray-400 text-xs text-center">
            <div>
                <div class="border-b border-gray-500 w-3/4 mx-auto mb-2"></div>
                <p class="font-black text-gray-900 uppercase">Firma Conductor / Inspector a Cargo</p>
                <p class="text-gray-500">Móvil {{ $patrol->device?->name }}</p>
            </div>
            <div>
                <div class="border-b border-gray-500 w-3/4 mx-auto mb-2"></div>
                <p class="font-black text-gray-900 uppercase">Firma Supervisor de Turno</p>
                <p class="text-gray-500">Central de Despacho y Monitoreo BuinSeguro</p>
            </div>
        </div>

    </div>

</body>
</html>
