<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informe Agrupado de Procedimientos ({{ $procedures->count() }}) - BuinSeguro</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .cad-watermark-overlay {
            pointer-events: none;
            user-select: none;
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: repeating-linear-gradient(
                -30deg,
                rgba(15, 23, 42, 0.04) 0px,
                rgba(15, 23, 42, 0.04) 24px,
                transparent 24px,
                transparent 48px
            );
        }
        .cad-watermark-text {
            transform: rotate(-28deg);
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.78);
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.85);
            text-align: center;
            line-height: 1.35;
            padding: 4px 10px;
            border: 1px solid rgba(255, 255, 255, 0.45);
            background: rgba(15, 23, 42, 0.35);
            border-radius: 4px;
        }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .print-sheet { box-shadow: none !important; border: none !important; margin: 0 !important; padding: 0 !important; max-width: 100% !important; }
            .page-break { page-break-after: always; break-after: page; }
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-900 font-sans py-8 px-4"
      x-data="{ watermark: {{ $watermark ? 'true' : 'false' }} }">

    {{-- BARRA SUPERIOR DE IMPRESIÓN (NO SE IMPRIME) --}}
    <div class="no-print max-w-4xl mx-auto mb-5 flex flex-wrap justify-between items-center gap-3 bg-gray-900 text-white p-3.5 rounded-lg shadow">
        <div class="flex items-center gap-3">
            <a href="{{ route('reports.operator') }}" class="text-xs font-bold text-gray-300 hover:text-white">
                ← Volver a Mis Gestiones
            </a>
            <span class="text-gray-600">|</span>
            <span class="text-xs font-bold text-emerald-300">Informe Agrupado: {{ $procedures->count() }} procedimiento(s)</span>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <label class="inline-flex items-center gap-2 text-xs font-bold text-gray-200 cursor-pointer select-none bg-white/10 px-3 py-1.5 rounded-md border border-white/15">
                <input type="checkbox" x-model="watermark" class="rounded border-gray-400 text-emerald-500 focus:ring-emerald-400">
                <span>Marca de agua en evidencias</span>
            </label>

            <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-black text-xs py-2 px-4 rounded shadow flex items-center gap-1.5">
                🖨️ Imprimir / Guardar PDF Único
            </button>
        </div>
    </div>

    {{-- PORTADA / ÍNDICE RESUMEN DEL LOTE --}}
    <div class="print-sheet page-break max-w-4xl mx-auto bg-white shadow-lg border border-gray-300 rounded-lg p-8 mb-8">
        <div class="flex justify-between items-start border-b-2 border-gray-800 pb-4 mb-6">
            <div>
                <p class="text-xs font-black uppercase tracking-widest text-gray-500">Ilustre Municipalidad de Buin</p>
                <h1 class="text-2xl font-black text-gray-900 uppercase">Dirección de Seguridad Pública — BuinSeguro</h1>
                <p class="text-sm font-semibold text-gray-600">Compilado Oficial de Procedimientos Seleccionados</p>
            </div>
            <div class="text-right text-xs">
                <div class="inline-block bg-gray-900 text-white px-3.5 py-1.5 rounded font-black text-sm">
                    {{ $procedures->count() }} FOLIO(S)
                </div>
                <p class="text-gray-500 mt-1">Emitido: {{ now()->format('d/m/Y H:i:s') }}</p>
                <p class="text-gray-700 font-bold">Por: {{ auth()->user()->name }}</p>
            </div>
        </div>

        @if($from || $to)
            <p class="text-xs text-gray-600 mb-4">
                <strong>Rango consultado:</strong> {{ $from ?: 'Inicio' }} a {{ $to ?: 'Hoy' }}
            </p>
        @endif

        <h2 class="text-xs font-black uppercase tracking-wider text-gray-700 mb-3">Índice de Procedimientos Incluidos</h2>
        <table class="min-w-full text-xs border border-gray-300 mb-6">
            <thead>
                <tr class="bg-gray-100 text-gray-700 uppercase font-bold border-b border-gray-300">
                    <th class="py-2 px-3 text-left">Folio</th>
                    <th class="py-2 px-3 text-left">Fecha / Hora</th>
                    <th class="py-2 px-3 text-left">Sector / Dirección</th>
                    <th class="py-2 px-3 text-left">Categoría</th>
                    <th class="py-2 px-3 text-center">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($procedures as $item)
                    <tr>
                        <td class="py-2 px-3 font-mono font-black">#{{ str_pad($item->id, 6, '0', STR_PAD_LEFT) }}</td>
                        <td class="py-2 px-3 font-mono whitespace-nowrap">{{ $item->created_at->format('d/m/Y H:i') }}</td>
                        <td class="py-2 px-3">{{ $item->sector->name ?? 'N/A' }} — {{ $item->exact_address }}</td>
                        <td class="py-2 px-3 font-bold">{{ $item->procedureType->name ?? 'N/A' }}</td>
                        <td class="py-2 px-3 text-center font-bold">{{ $item->status }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- FICHAS INDIVIDUALES EN UN ÚNICO PDF --}}
    @foreach($procedures as $procedure)
        <div class="print-sheet {{ ! $loop->last ? 'page-break mb-8' : '' }} max-w-4xl mx-auto bg-white shadow-lg border border-gray-300 rounded-lg p-8">
            <div class="flex justify-between items-start border-b-2 border-gray-800 pb-4 mb-5">
                <div>
                    <p class="text-xs font-black uppercase tracking-widest text-gray-500">Ilustre Municipalidad de Buin · BuinSeguro</p>
                    <h2 class="text-xl font-black text-gray-900 uppercase">Ficha de Procedimiento</h2>
                </div>
                <div class="text-right">
                    <div class="inline-block bg-gray-900 text-white px-3.5 py-1 rounded font-black text-base">
                        FOLIO #{{ str_pad($procedure->id, 6, '0', STR_PAD_LEFT) }}
                    </div>
                    <p class="text-xs font-bold uppercase mt-1">Estado: {{ $procedure->status }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5 text-xs">
                <div class="border border-gray-300 rounded p-3 space-y-1">
                    <div><strong>Categoría:</strong> {{ $procedure->procedureType->name ?? 'N/A' }}</div>
                    <div><strong>Sector:</strong> {{ $procedure->sector->name ?? 'N/A' }}</div>
                    <div><strong>Dirección:</strong> {{ $procedure->exact_address }}</div>
                    <div><strong>Recepción:</strong> {{ $procedure->created_at->format('d/m/Y H:i:s') }}</div>
                    <div><strong>Operador:</strong> {{ $procedure->user->name ?? 'Sistema' }}</div>
                    @if($procedure->derived_to)
                        <div><strong>Derivado a:</strong> {{ $procedure->derived_to }} {{ $procedure->external_reference ? "({$procedure->external_reference})" : '' }}</div>
                    @endif
                    @if($procedure->closure_resolution)
                        <div><strong>Resolución Cierre:</strong> {{ $procedure->closure_resolution }}</div>
                    @endif
                </div>
                <div class="border border-gray-300 rounded p-3 space-y-1">
                    <div><strong>Informante:</strong> {{ $procedure->caller_name ?: 'Anónimo' }}</div>
                    <div><strong>Teléfono:</strong> {{ $procedure->caller_phone ?: 'No registrado' }}</div>
                    <div><strong>Móvil Asignado:</strong> {{ $procedure->device->name ?? 'Sin asignar' }}</div>
                    <div><strong>Cierre:</strong> {{ $procedure->closed_at ? $procedure->closed_at->format('d/m/Y H:i:s') : 'En curso' }}</div>
                </div>
            </div>

            <div class="mb-5">
                <h3 class="text-xs font-black uppercase tracking-wider text-gray-700 border-b border-gray-400 pb-1 mb-2">
                    Bitácora Cronológica ({{ $procedure->logs->count() }})
                </h3>
                <table class="min-w-full text-xs border border-gray-300">
                    <thead>
                        <tr class="bg-gray-100 text-gray-700 uppercase font-bold border-b border-gray-300">
                            <th class="py-1.5 px-2 text-left w-32">Fecha / Hora</th>
                            <th class="py-1.5 px-2 text-left w-40">Acción</th>
                            <th class="py-1.5 px-2 text-left">Detalle</th>
                            <th class="py-1.5 px-2 text-left w-32">Funcionario</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($procedure->logs->sortBy('created_at') as $log)
                            <tr>
                                <td class="py-1.5 px-2 font-mono whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                                <td class="py-1.5 px-2 font-bold">{{ $log->action_type }}</td>
                                <td class="py-1.5 px-2">{{ $log->description }}</td>
                                <td class="py-1.5 px-2 text-gray-600">{{ $log->user->name ?? 'Sistema' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($procedure->evidences->count() > 0)
                <div>
                    <h3 class="text-xs font-black uppercase tracking-wider text-gray-700 border-b border-gray-400 pb-1 mb-2">
                        Registro Fotográfico ({{ $procedure->evidences->count() }})
                    </h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        @foreach($procedure->evidences as $ev)
                            <div class="border border-gray-300 rounded p-1.5 text-center">
                                <div class="relative overflow-hidden rounded mb-1">
                                    <img src="{{ asset('storage/' . $ev->file_path) }}" class="w-full h-36 object-cover">
                                    <div x-show="watermark" class="cad-watermark-overlay">
                                        <span class="cad-watermark-text">
                                            BUIN SEGURO<br>
                                            FOLIO #{{ str_pad($procedure->id, 6, '0', STR_PAD_LEFT) }}<br>
                                            {{ $ev->created_at->format('d/m/Y H:i') }}
                                        </span>
                                    </div>
                                </div>
                                <span class="text-[10px] text-gray-500 block">
                                    {{ $ev->created_at->format('d/m/Y H:i') }} · {{ $ev->user->name ?? '' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endforeach
</body>
</html>
