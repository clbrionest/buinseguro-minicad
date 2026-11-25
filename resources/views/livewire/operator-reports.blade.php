<div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

    {{-- Encabezado --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fa-solid fa-folder-user text-brand-cobalt dark:text-brand-cobalt-mid" aria-hidden="true"></i>
                <span>Mis Gestiones — Reportes del Operador</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Consulta los procedimientos y llamados gestionados por <strong>{{ auth()->user()->name }}</strong>
                @if(auth()->user()->extension)
                    (Anexo <span class="font-mono font-bold">{{ auth()->user()->extension }}</span>)
                @endif
                y exporta los casos seleccionados en un único PDF oficial.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if($this->selectedBatchPdfUrl)
                <a href="{{ $this->selectedBatchPdfUrl }}"
                   target="_blank"
                   class="inline-flex items-center gap-2 bg-brand-lime hover:bg-brand-lime-mid text-brand-black font-extrabold text-xs px-4 py-2.5 rounded-lg shadow-xs transition">
                    <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                    <span>Exportar {{ count($selectedProcedureIds) }} en 1 PDF</span>
                </a>
            @else
                <button type="button"
                        disabled
                        class="inline-flex items-center gap-2 bg-slate-200 text-slate-500 font-bold text-xs px-4 py-2.5 rounded-lg opacity-60 cursor-not-allowed">
                    <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                    <span>Selecciona casos para PDF</span>
                </button>
            @endif
        </div>
    </div>

    {{-- KPIs del Operador --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <x-cad.kpi-card label="Mis Casos Gestionados" :value="$totalMyProcedures" tone="cobalt" icon="fa-solid fa-folder-open" period="En el rango seleccionado" />
        <x-cad.kpi-card label="Casos Cerrados" :value="$closedMyProcedures" tone="completed" icon="fa-solid fa-circle-check" period="Finalizados en el período" />
        <x-cad.kpi-card label="Mis Llamados" :value="$totalMyCalls" tone="progress" icon="fa-solid fa-phone-volume" period="Anexo o vinculados a mis casos" />
        <x-cad.kpi-card label="Llamados con Caso" :value="$linkedMyCalls" tone="lime" icon="fa-solid fa-link" period="Asociados a un folio CAD" />
    </div>

    {{-- Filtros y Pestañas --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4 space-y-4">
        <div class="flex flex-col md:flex-row justify-between items-stretch md:items-center gap-3 border-b border-slate-200 pb-3">
            <div class="inline-flex rounded-lg border border-slate-200 bg-slate-50 p-1 text-xs font-bold">
                <button type="button"
                        wire:click="$set('activeTab', 'procedures')"
                        class="px-4 py-1.5 rounded-md transition {{ $activeTab === 'procedures' ? 'bg-brand-cobalt text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                    📋 Mis Casos ({{ $totalMyProcedures }})
                </button>
                <button type="button"
                        wire:click="$set('activeTab', 'calls')"
                        class="px-4 py-1.5 rounded-md transition {{ $activeTab === 'calls' ? 'bg-brand-cobalt text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                    📞 Mis Llamados ({{ $totalMyCalls }})
                </button>
            </div>

            @if($activeTab === 'procedures')
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <button type="button"
                            wire:click="selectAllFilteredProcedures"
                            class="px-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 font-bold transition">
                        ✓ Seleccionar todos (máx. 50)
                    </button>
                    @if(count($selectedProcedureIds) > 0)
                        <button type="button"
                                wire:click="clearSelection"
                                class="px-3 py-1.5 rounded-lg border border-red-200 bg-red-50 hover:bg-red-100 text-red-700 font-bold transition">
                            Limpiar ({{ count($selectedProcedureIds) }})
                        </button>
                    @endif
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Fecha Desde</label>
                <input type="date" wire:model.live="startDate" class="w-full text-xs rounded-lg border-slate-300 shadow-xs">
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Fecha Hasta</label>
                <input type="date" wire:model.live="endDate" class="w-full text-xs rounded-lg border-slate-300 shadow-xs">
            </div>
            @if($activeTab === 'procedures')
                <div>
                    <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Estado del Caso</label>
                    <select wire:model.live="statusFilter" class="w-full text-xs rounded-lg border-slate-300 shadow-xs font-semibold">
                        <option value="">Todos los estados</option>
                        <option value="Pendiente">Pendiente</option>
                        <option value="En Camino">En Camino</option>
                        <option value="En el Lugar">En el Lugar</option>
                        <option value="Derivado">Derivado</option>
                        <option value="Cerrado">Cerrado</option>
                    </select>
                </div>
            @endif
            <div class="{{ $activeTab === 'calls' ? 'md:col-span-2' : '' }}">
                <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Buscar</label>
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="{{ $activeTab === 'procedures' ? 'Folio, dirección, sector o tipo...' : 'Número, tipificación o nota...' }}"
                       class="w-full text-xs rounded-lg border-slate-300 shadow-xs">
            </div>
        </div>
    </div>

    {{-- TAB 1: MIS CASOS GESTIONADOS --}}
    @if($activeTab === 'procedures')
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full leading-normal text-xs">
                    <thead>
                        <tr class="bg-slate-900 text-white uppercase tracking-wider">
                            <th class="py-3 px-4 text-center w-10">PDF</th>
                            <th class="py-3 px-4 text-left">Folio / Fecha</th>
                            <th class="py-3 px-4 text-left">Sector / Dirección</th>
                            <th class="py-3 px-4 text-left">Tipo / Móvil</th>
                            <th class="py-3 px-4 text-center">Estado</th>
                            <th class="py-3 px-4 text-center">Registros</th>
                            <th class="py-3 px-4 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($procedures as $proc)
                            <tr wire:key="op-proc-{{ $proc->id }}" class="hover:bg-slate-50/80 transition">
                                <td class="py-3 px-4 text-center">
                                    <input type="checkbox"
                                           wire:model.live="selectedProcedureIds"
                                           value="{{ $proc->id }}"
                                           aria-label="Seleccionar Caso #{{ $proc->id }} para PDF agrupado"
                                           class="rounded border-slate-300 text-brand-cobalt focus:ring-brand-cobalt">
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <div class="font-black text-slate-900 font-mono">#{{ $proc->id }}</div>
                                    <div class="text-[11px] text-slate-500 font-mono">{{ $proc->created_at->format('d/m/Y H:i') }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900">{{ $proc->sector->name ?? 'N/A' }}</div>
                                    <div class="text-[11px] text-slate-500">{{ $proc->exact_address }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-800">{{ $proc->procedureType->name ?? 'N/A' }}</div>
                                    <div class="text-[11px] text-slate-500">{{ $proc->device ? '🚓 '.$proc->device->name : 'Sin móvil' }}</div>
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <x-cad.procedure-status :status="$proc->status" />
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap text-[11px] text-slate-600 font-semibold">
                                    <span title="Actuaciones en bitácora">📝 {{ $proc->logs_count }}</span>
                                    <span class="mx-1 text-slate-300">·</span>
                                    <span title="Evidencias fotográficas">📷 {{ $proc->evidences_count }}</span>
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ route('procedures.show', $proc->id) }}" class="text-blue-600 hover:underline font-bold">Abrir</a>
                                        <a href="{{ route('procedures.print', $proc->id) }}"
                                           target="_blank"
                                           class="px-2 py-1 rounded border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 font-bold">
                                            🖨️ PDF
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-10 text-center text-slate-400 text-sm">
                                    No registras procedimientos gestionados en el rango seleccionado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($procedures->hasPages())
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200">
                    {{ $procedures->links() }}
                </div>
            @endif
        </div>
    @else
        {{-- TAB 2: MIS LLAMADOS --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full leading-normal text-xs">
                    <thead>
                        <tr class="bg-slate-900 text-white uppercase tracking-wider">
                            <th class="py-3 px-4 text-left">Fecha / Hora</th>
                            <th class="py-3 px-4 text-left">Número Origen</th>
                            <th class="py-3 px-4 text-center">Estado</th>
                            <th class="py-3 px-4 text-center">Grabación</th>
                            <th class="py-3 px-4 text-left">Caso / Tipificación</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($calls as $call)
                            <tr wire:key="op-call-{{ $call->id }}" class="hover:bg-slate-50/80 transition">
                                <td class="py-3 px-4 whitespace-nowrap font-mono">
                                    <div class="font-bold text-slate-900">{{ $call->created_at->format('d/m/Y') }}</div>
                                    <div class="text-[11px] text-slate-500">{{ $call->created_at->format('H:i:s') }}</div>
                                </td>
                                <td class="py-3 px-4 font-mono font-black text-blue-800 whitespace-nowrap">
                                    {{ $call->caller_id }}
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ $call->status }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    @if($call->recording_path)
                                        <audio wire:key="op-call-audio-{{ $call->id }}" controls class="h-7 w-40 inline-block" preload="none" src="{{ route('calls.play', $call->id) }}">
                                            <source src="{{ route('calls.play', $call->id) }}" type="audio/wav">
                                        </audio>
                                    @else
                                        <span class="text-slate-400 italic">Sin audio</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    @if($call->procedure_id)
                                        <a href="{{ route('procedures.show', $call->procedure_id) }}" class="font-bold text-indigo-600 hover:underline">
                                            Caso #{{ $call->procedure_id }}
                                        </a>
                                    @elseif($call->classification)
                                        <div class="font-bold text-amber-800">{{ $call->classification }}</div>
                                        @if($call->notes)
                                            <div class="text-[11px] text-slate-500 mt-0.5">{{ $call->notes }}</div>
                                        @endif
                                    @else
                                        <span class="text-slate-400 italic">Sin caso ni tipificación</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-10 text-center text-slate-400 text-sm">
                                    No se encontraron llamados asociados a tu gestión en este rango de fechas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($calls->hasPages())
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200">
                    {{ $calls->links() }}
                </div>
            @endif
        </div>
    @endif
</div>
