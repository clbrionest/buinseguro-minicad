<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha Oficial Procedimiento #{{ $procedure->id }} - BuinSeguro</title>
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
            <a href="{{ route('procedures.show', $procedure->id) }}" class="text-xs font-bold text-gray-300 hover:text-white">
                ← Volver al Procedimiento #{{ $procedure->id }}
            </a>
            <span class="text-gray-600">|</span>
            <span class="text-xs text-gray-300">Vista Previa de Ficha Oficial para Fiscalía / Carabineros / Alcaldía</span>
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
                <p class="text-sm font-semibold text-gray-600">Central de Despacho y Monitoreo Operativo</p>
            </div>
            <div class="text-right">
                <div class="inline-block bg-gray-900 text-white px-4 py-1.5 rounded font-black text-lg">
                    FOLIO #{{ str_pad($procedure->id, 6, '0', STR_PAD_LEFT) }}
                </div>
                <p class="text-xs text-gray-500 mt-1">
                    Emitido: {{ now()->format('d/m/Y H:i:s') }}
                </p>
                <p class="text-xs font-bold uppercase mt-0.5">
                    Estado: {{ $procedure->status }}
                </p>
            </div>
        </div>

        {{-- SECCIÓN 1: RESUMEN EJECUTIVO Y TIEMPOS SLA --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6 bg-gray-50 p-4 rounded border border-gray-200 text-xs">
            <div>
                <span class="block text-gray-500 uppercase font-bold">Hora Recepción</span>
                <span class="text-sm font-black text-gray-900">{{ $procedure->created_at->format('d/m/Y H:i:s') }}</span>
            </div>
            <div>
                <span class="block text-gray-500 uppercase font-bold">Hora Despacho</span>
                <span class="text-sm font-black text-gray-900">
                    {{ $procedure->dispatched_at ? $procedure->dispatched_at->format('H:i:s') . ' (' . $procedure->dispatch_minutes . ' min)' : 'No aplica / Sin despacho' }}
                </span>
            </div>
            <div>
                <span class="block text-gray-500 uppercase font-bold">Llegada Lugar (SLA)</span>
                <span class="text-sm font-black text-gray-900">
                    {{ $procedure->arrived_at ? $procedure->arrived_at->format('H:i:s') . ' (' . $procedure->arrival_minutes . ' min)' : ($procedure->mobile_arrived_at ? $procedure->mobile_arrived_at->format('H:i:s') . ' (GPS)' : 'Pendiente / Sin registro') }}
                </span>
            </div>
            <div>
                <span class="block text-gray-500 uppercase font-bold">Hora Cierre</span>
                <span class="text-sm font-black text-gray-900">
                    {{ $procedure->closed_at ? $procedure->closed_at->format('d/m/Y H:i:s') : 'En curso' }}
                </span>
            </div>
        </div>

        {{-- SECCIÓN 2: DATOS DEL INCIDENTE Y UBICACIÓN --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="border border-gray-300 rounded p-4">
                <h2 class="text-xs font-black uppercase tracking-wider text-gray-700 border-b pb-1.5 mb-3">1. Antecedentes del Procedimiento</h2>
                <div class="space-y-2 text-sm">
                    <div><span class="font-bold text-gray-600">Categoría de Inicio (Llamado):</span> {{ $procedure->initialProcedureType->name ?? ($procedure->procedureType->name ?? 'N/A') }}</div>
                    <div><span class="font-bold text-gray-600">Categoría Operativa Confirmada:</span> <span class="font-black">{{ $procedure->procedureType->name ?? 'N/A' }}</span></div>
                    <div><span class="font-bold text-gray-600">Vía de Ingreso:</span> {{ $procedure->origin->name ?? 'N/A' }} {{ $procedure->is_preventive ? '(Patrullaje Preventivo)' : '' }}</div>
                    <div><span class="font-bold text-gray-600">Sector Jurisdiccional:</span> {{ $procedure->sector->name ?? 'N/A' }}</div>
                    <div><span class="font-bold text-gray-600">Dirección Exacta:</span> {{ $procedure->exact_address }}</div>
                    @if($procedure->address_reference)
                        <div><span class="font-bold text-gray-600">Referencia:</span> {{ $procedure->address_reference }}</div>
                    @endif
                    @if($procedure->latitude && $procedure->longitude)
                        <div><span class="font-bold text-gray-600">Coordenadas Incidente:</span> {{ $procedure->latitude }}, {{ $procedure->longitude }}</div>
                    @endif
                    <div><span class="font-bold text-gray-600">Operador Receptor:</span> {{ $procedure->user->name ?? 'Sistema' }}</div>
                </div>
            </div>

            <div class="border border-gray-300 rounded p-4">
                <h2 class="text-xs font-black uppercase tracking-wider text-gray-700 border-b pb-1.5 mb-3">2. Informante y Unidad Asignada</h2>
                <div class="space-y-2 text-sm">
                    <div><span class="font-bold text-gray-600">Nombre Informante:</span> {{ $procedure->caller_name ?: 'Anónimo / No indica' }}</div>
                    <div><span class="font-bold text-gray-600">Teléfono Contacto:</span> {{ $procedure->caller_phone ?: 'No registrado' }}</div>
                    <div><span class="font-bold text-gray-600">RUT / Documento:</span> {{ $procedure->caller_id_document ?: 'No registrado' }}</div>
                    <div class="pt-2 border-t border-gray-200">
                        <span class="font-bold text-gray-600">Móvil Asignado:</span>
                        <span class="font-black">{{ $procedure->device->name ?? 'Sin Asignar' }}</span>
                    </div>
                    @if($procedure->mobile_arrived_at)
                        <div class="text-xs text-gray-700">
                            <span class="font-bold">Georreferencia Arribo Móvil:</span>
                            {{ $procedure->mobile_arrived_at->format('H:i:s') }}
                            @if(!is_null($procedure->mobile_arrival_distance))
                                (Distancia validada: {{ $procedure->mobile_arrival_distance }} m)
                            @endif
                        </div>
                    @endif
                    @if($procedure->patrolAssignment && $procedure->patrolAssignment->crew->count() > 0)
                        <div class="pt-1">
                            <span class="font-bold text-gray-600 block text-xs uppercase mb-1">Tripulación en Turno:</span>
                            <ul class="list-disc list-inside text-xs text-gray-800">
                                @foreach($procedure->patrolAssignment->crew as $member)
                                    <li><strong>{{ $member->name }}</strong> ({{ $member->pivot->role }})</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- SECCIÓN 2B: ANTECEDENTES ESPECÍFICOS / METADATOS DE LA CATEGORÍA --}}
        @php $formattedMeta = $procedure->formatted_metadata; @endphp
        @if(count($formattedMeta) > 0)
            <div class="mb-6 border border-gray-300 rounded p-4 bg-gray-50">
                <h2 class="text-xs font-black uppercase tracking-wider text-gray-700 border-b border-gray-300 pb-1.5 mb-3">
                    Antecedentes Específicos Registrados ({{ $procedure->procedureType->name ?? '' }})
                </h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                    @foreach($formattedMeta as $meta)
                        <div class="bg-white border border-gray-200 rounded p-2">
                            <span class="block text-gray-500 font-bold uppercase text-[10px]">{{ $meta['label'] }}</span>
                            <span class="font-black text-gray-900 text-sm">{{ $meta['value'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- SECCIÓN 3: BITÁCORA CRONOLÓGICA DE ACTUACIONES --}}
        <div class="mb-6">
            <h2 class="text-xs font-black uppercase tracking-wider text-gray-700 border-b-2 border-gray-800 pb-1.5 mb-3">
                3. Bitácora Cronológica de Actuaciones
            </h2>
            <table class="min-w-full text-xs border border-gray-300">
                <thead>
                    <tr class="bg-gray-100 text-gray-700 uppercase font-bold border-b border-gray-300">
                        <th class="py-2 px-3 text-left w-36">Fecha / Hora</th>
                        <th class="py-2 px-3 text-left w-44">Acción</th>
                        <th class="py-2 px-3 text-left">Detalle / Observación</th>
                        <th class="py-2 px-3 text-left w-40">Funcionario</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($procedure->logs->sortBy('created_at') as $log)
                        <tr>
                            <td class="py-2 px-3 font-mono whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                            <td class="py-2 px-3 font-bold text-gray-800">{{ $log->action_type }}</td>
                            <td class="py-2 px-3 text-gray-700">{{ $log->description }}</td>
                            <td class="py-2 px-3 text-gray-600">{{ $log->user->name ?? 'Sistema' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- SECCIÓN 4: PROCEDIMIENTOS ANEXOS (SI EXISTEN) --}}
        @if($procedure->children->count() > 0)
            <div class="mb-6">
                <h2 class="text-xs font-black uppercase tracking-wider text-gray-700 border-b pb-1.5 mb-2">
                    4. Llamados / Reportes Anexados ({{ $procedure->children->count() }})
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                    @foreach($procedure->children as $child)
                        <div class="border border-gray-300 rounded p-2 bg-gray-50">
                            <strong>Caso #{{ $child->id }} ({{ $child->created_at->format('H:i') }}):</strong>
                            Informante: {{ $child->caller_name ?: 'Anónimo' }} ({{ $child->caller_phone ?: 'S/T' }})
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- SECCIÓN 5: EVIDENCIA FOTOGRÁFICA --}}
        @if($procedure->evidences->count() > 0)
            <div class="mb-8">
                <h2 class="text-xs font-black uppercase tracking-wider text-gray-700 border-b pb-1.5 mb-3">
                    5. Registro Fotográfico Adjunto ({{ $procedure->evidences->count() }})
                </h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                    @foreach($procedure->evidences as $ev)
                        <div class="border border-gray-300 rounded p-1.5 text-center">
                            <img src="{{ asset('storage/' . $ev->file_path) }}" class="w-full h-40 object-cover rounded mb-1">
                            <span class="text-[10px] text-gray-500 block">
                                Captura: {{ $ev->created_at->format('d/m/Y H:i') }} · {{ $ev->user->name ?? '' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- PIE DE FIRMAS --}}
        <div class="mt-14 pt-8 border-t border-gray-300 grid grid-cols-2 gap-12 text-center text-xs text-gray-600">
            <div>
                <div class="border-t border-gray-500 w-56 mx-auto pt-2 font-bold text-gray-800">
                    Firma Operador(a) Central / Despacho
                </div>
                <p>{{ $procedure->user->name ?? 'Central BuinSeguro' }}</p>
            </div>
            <div>
                <div class="border-t border-gray-500 w-56 mx-auto pt-2 font-bold text-gray-800">
                    V°B° Supervisor / Jefe de Turno
                </div>
                <p>Dirección de Seguridad Pública</p>
            </div>
        </div>

    </div>

</body>
</html>
