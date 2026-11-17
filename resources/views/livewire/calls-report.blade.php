<div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

    {{-- Notificaciones Flash --}}
    @if (session()->has('message'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-900 text-sm px-4 py-3 rounded-xl flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-emerald-600"></i>
                <span class="font-medium">{{ session('message') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-700 hover:text-emerald-900 text-sm font-bold">✕</button>
        </div>
    @endif

    {{-- Encabezado y Filtros --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-5 rounded-2xl border border-gray-200 shadow-xs">
        <div>
            <h2 class="text-2xl font-black text-gray-900 tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-phone-volume text-blue-600"></i>
                Historial Telefónico (Integración PBX)
            </h2>
            <p class="text-xs text-gray-500 mt-1">
                Registro de llamados entrantes en central Asterisk, grabaciones de audio y vinculación con casos CAD.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
            {{-- Filtro de Estado de Caso --}}
            <div class="inline-flex rounded-lg border border-gray-200 bg-gray-50 p-1 text-xs font-bold">
                <button type="button"
                        wire:click="$set('filterStatus', '')"
                        class="px-3 py-1.5 rounded-md transition {{ $filterStatus === '' ? 'bg-white text-gray-900 shadow-xs font-black' : 'text-gray-600 hover:text-gray-900' }}">
                    Todos
                </button>
                <button type="button"
                        wire:click="$set('filterStatus', 'unlinked')"
                        class="px-3 py-1.5 rounded-md transition {{ $filterStatus === 'unlinked' ? 'bg-amber-500 text-white shadow-xs font-black' : 'text-gray-600 hover:text-gray-900' }}">
                    Sin Caso
                </button>
                <button type="button"
                        wire:click="$set('filterStatus', 'linked')"
                        class="px-3 py-1.5 rounded-md transition {{ $filterStatus === 'linked' ? 'bg-indigo-600 text-white shadow-xs font-black' : 'text-gray-600 hover:text-gray-900' }}">
                    Vinculados
                </button>
            </div>

            {{-- Buscador --}}
            <div class="relative flex-1 md:w-64">
                <input wire:model.live.debounce.300ms="search"
                       type="text"
                       placeholder="Buscar número o anexo..."
                       class="w-full text-xs rounded-lg border-gray-300 pl-8 pr-3 py-2 shadow-xs focus:ring-blue-500 focus:border-blue-500">
                <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-2.5 text-gray-400 text-xs"></i>
            </div>
        </div>
    </div>

    {{-- Tabla de Llamadas --}}
    <div class="bg-white shadow-xs rounded-2xl overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full leading-normal">
                <thead>
                    <tr class="bg-slate-900 text-white text-xs uppercase tracking-wider">
                        <th class="py-3 px-4 text-left font-black">Fecha / Hora</th>
                        <th class="py-3 px-4 text-left font-black">Número Origen</th>
                        <th class="py-3 px-3 text-center font-black">Anexo Receptor</th>
                        <th class="py-3 px-3 text-center font-black">Estado PBX</th>
                        <th class="py-3 px-4 text-center font-black">Grabación</th>
                        <th class="py-3 px-4 text-center font-black">Caso Asociado</th>
                        <th class="py-3 px-4 text-center font-black">Operaciones / Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-xs divide-y divide-gray-100">
                    @forelse ($calls as $call)
                        <tr class="hover:bg-slate-50/80 transition {{ $call->procedure_id ? 'bg-indigo-50/20' : '' }}">
                            <td class="py-3 px-4 whitespace-nowrap">
                                <div class="font-bold text-gray-900">{{ $call->created_at->format('d/m/Y') }}</div>
                                <div class="text-[11px] font-mono text-gray-500">{{ $call->created_at->format('H:i:s') }}</div>
                            </td>

                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="font-mono font-black text-sm text-blue-800 tracking-wider">
                                    {{ $call->caller_id }}
                                </span>
                            </td>

                            <td class="py-3 px-3 text-center whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 bg-slate-100 px-2.5 py-1 rounded-md border border-slate-200 text-slate-700 font-bold font-mono">
                                    📞 {{ $call->extension ?: 'N/A' }}
                                </span>
                            </td>

                            <td class="py-3 px-3 text-center whitespace-nowrap">
                                @php
                                    $statusColors = match(strtolower((string) $call->status)) {
                                        'ringing' => 'bg-blue-100 text-blue-800 border-blue-200',
                                        'completed', 'answered' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                        'hangup' => 'bg-slate-100 text-slate-700 border-slate-200',
                                        default => 'bg-gray-100 text-gray-700 border-gray-200'
                                    };
                                @endphp
                                <span class="{{ $statusColors }} px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase border">
                                    {{ $call->status }}
                                </span>
                            </td>

                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @if($call->recording_path)
                                    <audio controls class="h-7 w-36 inline-block" preload="none">
                                        <source src="{{ route('calls.play', $call->id) }}" type="audio/wav">
                                        Audio no soportado.
                                    </audio>
                                @else
                                    <span class="text-[11px] text-gray-400 italic">Sin audio</span>
                                @endif
                            </td>

                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @if($call->procedure_id)
                                    <div class="inline-flex flex-col items-center">
                                        <span class="bg-indigo-100 text-indigo-800 px-2.5 py-0.5 rounded-full text-[10px] font-black border border-indigo-200">
                                            ✓ VINCULADO
                                        </span>
                                        <a href="{{ route('procedures.show', $call->procedure_id) }}"
                                           class="text-[11px] font-bold text-indigo-600 hover:text-indigo-900 underline mt-0.5">
                                            Caso Folio #{{ $call->procedure_id }}
                                        </a>
                                    </div>
                                @else
                                    @if($call->classification)
                                        <div class="inline-flex flex-col items-center">
                                            <span class="bg-amber-100 text-amber-800 px-2 py-0.5 rounded text-[10px] font-bold border border-amber-200">
                                                {{ $call->classification }}
                                            </span>
                                            <span class="text-[9px] text-gray-400">Sin caso creado</span>
                                        </div>
                                    @else
                                        <span class="bg-gray-100 text-gray-500 px-2 py-0.5 rounded text-[10px] font-bold border border-gray-200">
                                            SIN CASO
                                        </span>
                                    @endif
                                @endif
                            </td>

                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @if($call->procedure_id)
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ route('procedures.show', $call->procedure_id) }}"
                                           class="inline-flex items-center gap-1 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 px-2.5 py-1 rounded text-xs font-bold transition">
                                            <span>Ver Caso</span>
                                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                        </a>
                                        <button type="button"
                                                wire:click="unlinkCall({{ $call->id }})"
                                                wire:confirm="¿Seguro que deseas desvincular esta llamada del Caso #{{ $call->procedure_id }}?"
                                                class="text-red-500 hover:text-red-700 hover:underline text-[11px] font-semibold"
                                                title="Desvincular del caso">
                                            Desvincular
                                        </button>
                                    </div>
                                @else
                                    <div class="inline-flex items-center gap-1.5 flex-wrap justify-center">
                                        {{-- 1. Crear nuevo caso a partir de este llamado --}}
                                        <button type="button"
                                                wire:click="$dispatch('openModal', { caller_id: '{{ $call->caller_id }}', call_id: {{ $call->id }} })"
                                                class="inline-flex items-center gap-1 bg-emerald-600 hover:bg-emerald-700 text-white px-2.5 py-1 rounded text-xs font-bold shadow-xs transition"
                                                title="Crear un nuevo caso precargando este número y audio">
                                            <i class="fa-solid fa-plus-circle"></i>
                                            <span>Crear Caso</span>
                                        </button>

                                        {{-- 2. Amarrar a un caso ya existente --}}
                                        <button type="button"
                                                wire:click="openLinkModal({{ $call->id }})"
                                                class="inline-flex items-center gap-1 bg-indigo-600 hover:bg-indigo-700 text-white px-2.5 py-1 rounded text-xs font-bold shadow-xs transition"
                                                title="Amarrar esta grabación a un caso existente">
                                            <i class="fa-solid fa-link"></i>
                                            <span>Amarrar Caso</span>
                                        </button>

                                        {{-- 3. Tipificar (si fue broma o informativo sin caso) --}}
                                        <button type="button"
                                                wire:click="openClassifyModal({{ $call->id }})"
                                                class="inline-flex items-center gap-1 {{ $call->classification ? 'bg-gray-200 hover:bg-gray-300 text-gray-700' : 'bg-amber-500 hover:bg-amber-600 text-white' }} px-2 py-1 rounded text-xs font-bold transition"
                                                title="Tipificar llamada (broma, consulta informativa, etc.)">
                                            <i class="fa-solid fa-tag text-[10px]"></i>
                                            <span>{{ $call->classification ? 'Editar Tip.' : 'Tipificar' }}</span>
                                        </button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-gray-400 text-sm">
                                <i class="fa-solid fa-phone-slash text-3xl mb-2 text-gray-300 block"></i>
                                No se encontraron registros telefónicos con los filtros aplicados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
            {{ $calls->links() }}
        </div>
    </div>

    {{-- MODAL 1: AMARRAR LLAMADA A CASO EXISTENTE --}}
    @if($isLinkModalOpen && $selectedCallForLinking)
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
            <div class="relative bg-white rounded-2xl max-w-3xl w-full shadow-2xl border border-gray-200 overflow-hidden">
                
                {{-- Encabezado Modal --}}
                <div class="px-6 py-4 bg-slate-900 text-white flex justify-between items-center border-b border-white/10">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-widest text-emerald-400 block">
                            TELEFONÍA PBX & TRAZABILIDAD
                        </span>
                        <h3 class="text-base font-black flex items-center gap-2">
                            <i class="fa-solid fa-link text-indigo-400"></i>
                            Amarrar Llamada a Caso Existente
                        </h3>
                    </div>
                    <button type="button"
                            wire:click="closeLinkModal"
                            class="text-gray-400 hover:text-white p-1 focus:outline-none">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                {{-- Cuerpo del Modal --}}
                <div class="p-6 space-y-4">
                    {{-- Tarjeta Resumen de la Llamada --}}
                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                        <div>
                            <span class="text-[10px] font-bold uppercase text-slate-500 block">Llamada Seleccionada</span>
                            <div class="text-lg font-black text-slate-900 font-mono">
                                {{ $selectedCallForLinking->caller_id }}
                                <span class="text-xs font-semibold text-slate-500 font-sans">(Anexo: {{ $selectedCallForLinking->extension ?: '0' }})</span>
                            </div>
                            <div class="text-xs text-slate-500">
                                Recibida: {{ $selectedCallForLinking->created_at->format('d/m/Y H:i:s') }}
                            </div>
                        </div>

                        @if($selectedCallForLinking->recording_path)
                            <div class="w-full sm:w-auto">
                                <label class="text-[10px] font-bold uppercase text-slate-600 block mb-1">Escuchar Grabación:</label>
                                <audio controls class="h-8 w-full sm:w-48" preload="none">
                                    <source src="{{ route('calls.play', $selectedCallForLinking->id) }}" type="audio/wav">
                                </audio>
                            </div>
                        @endif
                    </div>

                    {{-- Buscador de Casos --}}
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-700 mb-1">
                            <i class="fa-solid fa-magnifying-glass text-slate-400 mr-1"></i>
                            Buscar Caso Existente (por Folio #, Solicitante, Dirección o Tipo)
                        </label>
                        <input type="text"
                               wire:model.live.debounce.300ms="procedureSearch"
                               placeholder="Ej: 45, San Martín, Robo, María González..."
                               class="w-full text-xs rounded-lg border-gray-300 py-2 shadow-xs focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    {{-- Lista de Casos Candidatos --}}
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-[10px] font-extrabold uppercase text-slate-500">
                                {{ $procedureSearch ? 'Resultados de Búsqueda:' : 'Casos Sugeridos / Recientes:' }}
                            </span>
                            <span class="text-[10px] text-slate-400">
                                Mostrando {{ $availableProcedures->count() }} casos
                            </span>
                        </div>

                        <div class="max-h-72 overflow-y-auto space-y-2 border border-slate-200 rounded-xl p-2 bg-slate-50/50">
                            @forelse($availableProcedures as $proc)
                                @php
                                    $isSamePhone = $proc->caller_phone && $proc->caller_phone === $selectedCallForLinking->caller_id;
                                @endphp
                                <div class="bg-white p-3 rounded-lg border {{ $isSamePhone ? 'border-emerald-300 ring-1 ring-emerald-200' : 'border-slate-200' }} shadow-xs flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 hover:border-indigo-300 transition">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-black text-sm text-slate-900">#{{ $proc->id }}</span>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-indigo-100 text-indigo-800">
                                                {{ $proc->procedureType->name ?? 'Caso' }}
                                            </span>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-100 text-slate-700">
                                                {{ $proc->status }}
                                            </span>
                                            @if($isSamePhone)
                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-100 text-emerald-800">
                                                    Mismo Teléfono
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-slate-700 font-medium">
                                            <i class="fa-solid fa-location-dot text-slate-400 text-[10px]"></i>
                                            {{ $proc->exact_address ?: 'Sin dirección' }}
                                            @if($proc->sector)
                                                <span class="text-slate-400 font-normal">({{ $proc->sector->name }})</span>
                                            @endif
                                        </div>
                                        <div class="text-[11px] text-slate-500">
                                            Solicitante: <span class="font-bold">{{ $proc->caller_name ?: 'Anónimo' }}</span>
                                            @if($proc->caller_phone)
                                                <span class="font-mono">({{ $proc->caller_phone }})</span>
                                            @endif
                                            • {{ $proc->created_at->format('d/m/Y H:i') }}
                                        </div>
                                    </div>

                                    <button type="button"
                                            wire:click="linkCallToProcedure({{ $proc->id }})"
                                            class="w-full sm:w-auto shrink-0 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs px-3 py-2 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5">
                                        <i class="fa-solid fa-link"></i>
                                        <span>Vincular a este Caso</span>
                                    </button>
                                </div>
                            @empty
                                <div class="py-8 text-center text-slate-400 text-xs">
                                    <i class="fa-solid fa-folder-open text-2xl mb-1 block text-slate-300"></i>
                                    No se encontraron casos con el término "{{ $procedureSearch }}".
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Footer Modal --}}
                <div class="px-6 py-3 bg-slate-50 border-t border-slate-200 flex justify-end">
                    <button type="button"
                            wire:click="closeLinkModal"
                            class="px-4 py-2 bg-white border border-slate-300 rounded-lg text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL 2: TIPIFICACIÓN DE LLAMADA (Broma, Consulta, etc.) --}}
    @if($isClassifyModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
            <div class="relative bg-white rounded-2xl max-w-md w-full shadow-2xl border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-slate-900 text-white flex justify-between items-center border-b border-white/10">
                    <h3 class="text-sm font-black flex items-center gap-2">
                        <i class="fa-solid fa-tag text-amber-400"></i>
                        Tipificar Llamada sin Procedimiento
                    </h3>
                    <button type="button" wire:click="closeClassifyModal" class="text-gray-400 hover:text-white p-1">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>
                
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Motivo / Clasificación *</label>
                        <select wire:model="classification" class="w-full text-xs border-gray-300 rounded-lg shadow-xs focus:border-blue-500 focus:ring focus:ring-blue-200">
                            <option value="">-- Seleccione un motivo --</option>
                            @foreach($callClassifications as $cOption)
                                <option value="{{ $cOption }}">{{ $cOption }}</option>
                            @endforeach
                        </select>
                        @error('classification') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Notas Adicionales (Opcional)</label>
                        <textarea wire:model="notes" rows="3" class="w-full text-xs border-gray-300 rounded-lg shadow-xs focus:border-blue-500 focus:ring focus:ring-blue-200" placeholder="Ej: Vecino consultaba por horario de feria..."></textarea>
                        @error('notes') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-end gap-2">
                    <button type="button" wire:click="closeClassifyModal" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-xs font-bold text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="button" wire:click="saveClassification" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold shadow-xs">
                        Guardar Tipificación
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
