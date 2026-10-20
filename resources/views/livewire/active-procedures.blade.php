<div wire:poll.10s>
    
    @if($activeEvent)
        <div class="mb-5 bg-gradient-to-r from-amber-600 to-red-600 rounded-xl shadow-lg p-4 text-white flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="flex items-center gap-3">
                <span class="p-2.5 bg-white/20 rounded-full text-2xl animate-pulse">🚨</span>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-black uppercase tracking-wider bg-black/30 px-2 py-0.5 rounded">{{ $activeEvent->event_type }} EN CURSO</span>
                        <span class="text-xs opacity-90">Iniciado {{ $activeEvent->created_at->format('d/m H:i') }}</span>
                    </div>
                    <h3 class="text-xl font-black mt-0.5">{{ $activeEvent->name }}</h3>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div class="flex gap-3 text-center bg-black/20 px-4 py-2 rounded-lg">
                    <div>
                        <span class="block text-xl font-black">{{ $activeEvent->reports_count }}</span>
                        <span class="text-[10px] uppercase font-bold opacity-80">Reportes</span>
                    </div>
                    <div class="border-l border-white/30 pl-3">
                        <span class="block text-xl font-black text-yellow-200">{{ $activeEvent->pending_reports_count }}</span>
                        <span class="text-[10px] uppercase font-bold opacity-80">Pendientes</span>
                    </div>
                    <div class="border-l border-white/30 pl-3">
                        <span class="block text-xl font-black text-green-200">{{ $activeEvent->in_progress_reports_count }}</span>
                        <span class="text-[10px] uppercase font-bold opacity-80">En Atención</span>
                    </div>
                </div>
                <a href="{{ route('events.manage') }}" class="bg-white text-red-700 hover:bg-slate-100 font-black py-2.5 px-4 rounded-lg shadow text-sm whitespace-nowrap transition">
                    ⚡ Operar Evento Masivo →
                </a>
            </div>
        </div>
    @endif

    {{-- BARRA DE ESTADO DE TURNO DE SALA (APERTURA / CONSIGNAS / CIERRE) --}}
    @hasanyrole('Administrador|Supervisor|Operador')
        @if($activeShift)
            <div class="mb-4 bg-slate-900 border border-slate-800 rounded-xl px-4 py-2.5 text-white flex flex-col md:flex-row justify-between items-start md:items-center gap-3 shadow-sm">
                <div class="flex flex-wrap items-center gap-3 text-xs">
                    <span class="inline-flex items-center gap-1.5 bg-emerald-500/20 border border-emerald-500/40 text-emerald-300 font-black px-2.5 py-1 rounded-lg uppercase">
                        <i class="fa-solid fa-tower-broadcast animate-pulse"></i> {{ $activeShift->shift_type }} Activo
                    </span>
                    <span class="text-slate-300">
                        <i class="fa-solid fa-user-check text-blue-400 mr-1"></i> Operador a cargo: <strong class="text-white">{{ $activeShift->openedBy->name ?? 'N/A' }}</strong>
                    </span>
                    <span class="text-slate-400">
                        <i class="fa-regular fa-clock mr-1"></i> Aperturado {{ $activeShift->started_at->format('d/m H:i') }} hrs
                    </span>
                    @if($lastClosedShift && $lastClosedShift->handover_notes)
                        <span class="bg-amber-500/20 border border-amber-500/40 text-amber-200 px-2.5 py-0.5 rounded-md font-semibold truncate max-w-md" title="{{ $lastClosedShift->handover_notes }}">
                            <i class="fa-solid fa-triangle-exclamation text-amber-400 mr-1"></i> Consigna guardia anterior: {{ \Illuminate\Support\Str::limit($lastClosedShift->handover_notes, 65) }}
                        </span>
                    @endif
                </div>
                <a href="{{ route('shifts.manage') }}" class="text-xs font-bold bg-blue-600 hover:bg-blue-500 text-white px-3 py-1.5 rounded-lg inline-flex items-center gap-1.5 transition shrink-0">
                    <i class="fa-solid fa-clipboard-check"></i> Bitácora / Entrega de Turno
                </a>
            </div>
        @else
            <div class="mb-4 bg-amber-50 border-l-4 border-amber-500 rounded-r-xl px-4 py-3 flex flex-col md:flex-row justify-between items-start md:items-center gap-3 shadow-sm">
                <div class="text-xs text-amber-900">
                    <div class="font-black uppercase flex items-center gap-1.5 text-amber-800">
                        <i class="fa-solid fa-circle-exclamation text-amber-600"></i> Sin Turno de Sala Abierto en este momento
                    </div>
                    <p class="mt-0.5 text-amber-700">
                        Recuerde realizar la <strong>Recepción y Apertura de Guardia</strong> al iniciar su turno para registrar novedades y habilitar el envío del reporte ejecutivo al cierre.
                        @if($lastClosedShift && $lastClosedShift->handover_notes)
                            <span class="block mt-1 font-bold text-amber-900">📌 Consigna dejada por {{ $lastClosedShift->closedBy->name ?? $lastClosedShift->openedBy->name ?? 'turno saliente' }}: "{{ $lastClosedShift->handover_notes }}"</span>
                        @endif
                    </p>
                </div>
                <a href="{{ route('shifts.manage') }}" class="bg-amber-600 hover:bg-amber-700 text-white font-black text-xs px-4 py-2 rounded-lg shadow inline-flex items-center gap-1.5 transition shrink-0">
                    <i class="fa-solid fa-play"></i> Abrir / Recibir Turno Ahora
                </a>
            </div>
        @endif
    @endhasanyrole

    {{-- CABECERA DE CONSOLA CAD --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-5 gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-800 tracking-tight flex items-center gap-2">
                <span><i class="fa-solid fa-satellite-dish text-blue-600 mr-1"></i> Central de Comunicaciones y Despacho</span>
                <span class="inline-flex items-center gap-1 text-[11px] font-bold bg-emerald-100 text-emerald-800 px-2.5 py-0.5 rounded-full">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span> EN LÍNEA (10s)
                </span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">Monitoreo en tiempo real de procedimientos activos, flota QAP, tiempos SLA y metadatos operativos.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('field.inspector') }}" class="bg-slate-800 hover:bg-slate-900 text-white font-bold py-2 px-3.5 rounded-lg shadow text-xs flex items-center gap-1.5 transition">
                <i class="fa-solid fa-mobile-screen-button"></i> Consola Terreno
            </a>
            @hasanyrole('Administrador|Supervisor|Operador')
            <a href="{{ route('events.manage') }}" class="bg-amber-500 hover:bg-amber-600 text-slate-900 font-black py-2 px-3.5 rounded-lg shadow text-xs flex items-center gap-1.5 transition">
                🌩️ Contingencias
            </a>
            @endhasanyrole
            <livewire:create-procedure />
        </div>
    </div>

    {{-- TARJETAS KPI INTERACTIVAS (CLICABLES PARA FILTRAR LA TABLA) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
        <button type="button"
                wire:click="toggleStatusFilter('Pendiente')"
                class="text-left bg-white rounded-xl shadow-sm p-4 border-l-4 border-red-500 flex justify-between items-center transition {{ $pendientes > 0 ? 'bg-red-50/70' : '' }} {{ $filterStatus === 'Pendiente' ? 'ring-2 ring-red-600' : 'hover:bg-slate-50' }}">
            <div>
                <p class="text-xs font-black text-slate-500 uppercase tracking-wider">Sin Despachar (Sala)</p>
                <h3 class="text-3xl font-black text-red-600">{{ $pendientes }}</h3>
                <span class="text-[10px] text-slate-400 font-semibold">{{ $filterStatus === 'Pendiente' ? '✓ Filtro activo (clic para quitar)' : 'Clic para filtrar' }}</span>
            </div>
            <div class="p-3 bg-red-100 rounded-xl text-red-600 {{ $pendientes > 0 ? 'animate-pulse' : '' }}">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </button>

        <button type="button"
                wire:click="toggleStatusFilter('En Camino')"
                class="text-left bg-white rounded-xl shadow-sm p-4 border-l-4 border-amber-400 flex justify-between items-center transition {{ $filterStatus === 'En Camino' ? 'ring-2 ring-amber-500 bg-amber-50/40' : 'hover:bg-slate-50' }}">
            <div>
                <p class="text-xs font-black text-slate-500 uppercase tracking-wider">Móvil en Ruta</p>
                <h3 class="text-3xl font-black text-amber-600">{{ $enCamino }}</h3>
                <span class="text-[10px] text-slate-400 font-semibold">{{ $filterStatus === 'En Camino' ? '✓ Filtro activo (clic para quitar)' : 'Clic para filtrar' }}</span>
            </div>
            <div class="p-3 bg-amber-100 rounded-xl text-amber-600">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            </div>
        </button>

        <button type="button"
                wire:click="toggleStatusFilter('En el Lugar')"
                class="text-left bg-white rounded-xl shadow-sm p-4 border-l-4 border-blue-500 flex justify-between items-center transition {{ $filterStatus === 'En el Lugar' ? 'ring-2 ring-blue-600 bg-blue-50/40' : 'hover:bg-slate-50' }}">
            <div>
                <p class="text-xs font-black text-slate-500 uppercase tracking-wider">Móvil en el Lugar</p>
                <h3 class="text-3xl font-black text-blue-600">{{ $enElLugar }}</h3>
                <span class="text-[10px] text-slate-400 font-semibold">{{ $filterStatus === 'En el Lugar' ? '✓ Filtro activo (clic para quitar)' : 'Clic para filtrar' }}</span>
            </div>
            <div class="p-3 bg-blue-100 rounded-xl text-blue-600">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
            </div>
        </button>

        @php
            $slaAvgColor = is_null($avgArrivalMinutes)
                ? 'border-slate-400 text-slate-700'
                : ($avgArrivalMinutes <= 12 ? 'border-emerald-500 text-emerald-600' : ($avgArrivalMinutes <= 20 ? 'border-amber-500 text-amber-600' : 'border-red-500 text-red-600'));
        @endphp
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 {{ $slaAvgColor }} flex justify-between items-center">
            <div>
                <p class="text-xs font-black text-slate-500 uppercase tracking-wider">SLA Arribo Hoy</p>
                <h3 class="text-2xl font-black">
                    {{ !is_null($avgArrivalMinutes) ? $avgArrivalMinutes . ' min' : 'S/D' }}
                </h3>
                <p class="text-[11px] text-slate-500 mt-0.5">
                    Despacho prom: <strong>{{ !is_null($avgDispatchMinutes) ? $avgDispatchMinutes . 'm' : 'S/D' }}</strong>
                </p>
            </div>
            <div class="p-3 bg-slate-100 rounded-xl text-slate-600">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>
    </div>

    {{-- MONITOR DE ESTADO DE FLOTA (QAP) --}}
    @if(isset($fleetDevices) && $fleetDevices->count() > 0)
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-5" x-data="{ openFleet: true }">
            <div class="flex justify-between items-center">
                <div class="flex flex-wrap items-center gap-2.5">
                    <h3 class="text-xs font-black uppercase text-slate-700 tracking-wider flex items-center gap-1.5">
                        🚓 Monitor de Flota Operativa (QAP)
                    </h3>
                    <span class="text-xs bg-emerald-100 text-emerald-800 px-2.5 py-0.5 rounded-full font-bold">
                        {{ $fleetDevices->filter(fn($d) => $d->effective_status === 'Disponible (QAP)')->count() }} Disponibles
                    </span>
                    <span class="text-xs bg-blue-100 text-blue-800 px-2.5 py-0.5 rounded-full font-bold">
                        {{ $fleetDevices->filter(fn($d) => $d->effective_status === 'En Procedimiento')->count() }} En Procedimiento
                    </span>
                </div>
                <button @click="openFleet = !openFleet" class="text-xs font-bold text-slate-500 hover:text-slate-800">
                    <span x-show="openFleet">Compactar ▲</span>
                    <span x-show="!openFleet">Desplegar Flota ▼</span>
                </button>
            </div>

            <div x-show="openFleet" class="mt-3 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                @foreach($fleetDevices as $dev)
                    @php
                        $effStatus = $dev->effective_status;
                        $cardStyle = match($effStatus) {
                            'Disponible (QAP)' => 'border-emerald-500 bg-emerald-50/40',
                            'En Procedimiento' => 'border-blue-500 bg-blue-50/50',
                            'En Colación' => 'border-amber-400 bg-amber-50/40',
                            'Punto Fijo' => 'border-purple-400 bg-purple-50/40',
                            'En Mantención' => 'border-red-400 bg-red-50/40',
                            default => 'border-slate-300 bg-slate-50',
                        };
                        $badgeStyle = match($effStatus) {
                            'Disponible (QAP)' => 'bg-emerald-600 text-white',
                            'En Procedimiento' => 'bg-blue-600 text-white',
                            'En Colación' => 'bg-amber-500 text-white',
                            'Punto Fijo' => 'bg-purple-600 text-white',
                            'En Mantención' => 'bg-red-600 text-white',
                            default => 'bg-slate-600 text-white',
                        };
                    @endphp
                    <div class="border-l-4 rounded-lg border p-2.5 flex flex-col justify-between {{ $cardStyle }}">
                        <div>
                            <div class="flex justify-between items-start gap-1">
                                <span class="font-black text-slate-800 text-sm">🚓 {{ $dev->name }}</span>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $badgeStyle }}">
                                    {{ $effStatus }}
                                    @if($dev->active_procedures_count > 0)
                                        ({{ $dev->active_procedures_count }})
                                    @endif
                                </span>
                            </div>
                            @if($dev->activePatrolAssignment)
                                <div class="text-[11px] text-slate-600 mt-1">
                                    <span class="font-bold">{{ $dev->activePatrolAssignment->sector->name ?? 'Sin sector' }}</span>
                                    @if($dev->activePatrolAssignment->crew->count() > 0)
                                        · {{ $dev->activePatrolAssignment->crew->pluck('name')->join(', ') }}
                                    @endif
                                </div>
                            @else
                                <div class="text-[11px] text-slate-400 italic mt-1">Sin turno activo asignado</div>
                            @endif
                        </div>

                        <div class="mt-2 pt-1.5 border-t border-slate-200/70 flex justify-between items-center gap-2">
                            <select wire:change="updateDeviceStatus({{ $dev->id }}, $event.target.value)" class="text-[11px] py-0.5 px-1.5 rounded border-slate-300 bg-white text-slate-700 font-semibold w-full">
                                @foreach(['Disponible (QAP)', 'En Colación', 'Punto Fijo', 'En Mantención'] as $st)
                                    <option value="{{ $st }}" @selected(($dev->operational_status ?: 'Disponible (QAP)') === $st)>
                                        Estado base: {{ $st }}
                                    </option>
                                @endforeach
                            </select>
                            @if($dev->last_location_at)
                                <span class="text-[10px] text-emerald-700 font-bold whitespace-nowrap" title="Último GPS: {{ $dev->last_location_at->format('H:i:s') }}">
                                    📡 {{ $dev->last_location_at->diffForHumans(null, true, true) }}
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- TABLA PRINCIPAL DE CASOS ACTIVOS CON BUSCADOR Y FILTROS RÁPIDOS --}}
    <div class="bg-white shadow-md rounded-xl overflow-hidden border border-slate-200">
        <div class="bg-slate-900 px-5 py-3.5 border-b border-slate-800 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-3">
            <div class="flex items-center gap-3">
                <h3 class="text-white font-black text-sm uppercase tracking-wider">📋 Cola Operativa de Procedimientos</h3>
                <span class="bg-blue-600 text-xs text-white px-2.5 py-0.5 rounded-full font-black">{{ $procedures->count() }} Casos</span>
            </div>

            <div class="flex flex-wrap items-center gap-2.5 w-full lg:w-auto">
                <div class="relative flex-1 sm:w-64">
                    <input type="text"
                           wire:model.live.debounce.300ms="search"
                           placeholder="🔍 Buscar #Folio, calle, PPU, vecino..."
                           class="w-full bg-slate-800 text-white placeholder-slate-400 border border-slate-700 rounded-lg px-3 py-1.5 text-xs focus:ring-blue-500 focus:border-blue-500">
                </div>

                <select wire:model.live="filterSector" class="bg-slate-800 text-white border border-slate-700 rounded-lg px-2.5 py-1.5 text-xs font-semibold">
                    <option value="">Todos los Sectores</option>
                    @foreach($sectors as $sec)
                        <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                    @endforeach
                </select>

                @if($search || $filterStatus || $filterSector)
                    <button type="button"
                            wire:click="$set('search', ''); $set('filterStatus', ''); $set('filterSector', '')"
                            class="text-xs bg-red-600 hover:bg-red-700 text-white font-bold px-2.5 py-1.5 rounded-lg transition">
                        ✕ Limpiar
                    </button>
                @endif
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full leading-normal">
                <thead>
                    <tr class="bg-slate-100 text-slate-600 uppercase text-[11px] tracking-wider font-black border-b border-slate-200">
                        <th class="py-3 px-5 text-left">Folio / Hora</th>
                        <th class="py-3 px-5 text-left">Sector y Ubicación</th>
                        <th class="py-3 px-5 text-left">Categorización y Antecedentes (Metadatos)</th>
                        <th class="py-3 px-5 text-center">Unidad Asignada</th>
                        <th class="py-3 px-5 text-center">Estado</th>
                        <th class="py-3 px-5 text-center">Semáforo SLA</th>
                        <th class="py-3 px-5 text-center">Acción</th>
                    </tr>
                </thead>
                <tbody class="text-slate-600 text-sm divide-y divide-slate-200">
                    @forelse ($procedures as $procedure)
                        <tr class="hover:bg-slate-50/90 transition">
                            <td class="py-3.5 px-5 text-left whitespace-nowrap">
                                <span class="font-black text-slate-900 text-sm">#{{ $procedure->id }}</span>
                                <div class="text-xs font-mono font-bold text-slate-500">{{ $procedure->created_at->format('H:i') }} hrs</div>
                            </td>
                            <td class="py-3.5 px-5 text-left">
                                <div class="font-black text-slate-800">{{ $procedure->sector->name ?? 'N/A' }}</div>
                                <div class="text-xs text-slate-600 font-medium">{{ $procedure->exact_address }}</div>
                                @if($procedure->caller_name || $procedure->caller_phone)
                                    <div class="text-[11px] text-slate-400 mt-0.5">
                                        👤 {{ $procedure->caller_name ?: 'Vecino' }} {{ $procedure->caller_phone ? "({$procedure->caller_phone})" : '' }}
                                    </div>
                                @endif
                            </td>
                            <td class="py-3.5 px-5 text-left">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="font-black text-slate-800">
                                        {{ $procedure->procedureType->name ?? 'N/A' }}
                                    </span>
                                    @if($procedure->initial_procedure_type_id && $procedure->initial_procedure_type_id != $procedure->procedure_type_id)
                                        <span class="text-[10px] bg-amber-100 text-amber-800 border border-amber-300 px-1.5 py-0.5 rounded font-bold" title="Ingresó inicialmente como: {{ $procedure->initialProcedureType->name ?? '' }}">
                                            Inicial: {{ $procedure->initialProcedureType->name ?? '' }}
                                        </span>
                                    @endif
                                </div>

                                {{-- Píldoras de Metadatos Específicos --}}
                                @php $metaList = $procedure->formatted_metadata; @endphp
                                @if(count($metaList) > 0)
                                    <div class="flex flex-wrap gap-1 mt-1.5">
                                        @foreach(array_slice($metaList, 0, 4) as $m)
                                            <span class="text-[11px] bg-indigo-50 text-indigo-900 border border-indigo-200 px-2 py-0.5 rounded font-semibold">
                                                <strong>{{ $m['label'] }}:</strong> {{ $m['value'] }}
                                            </span>
                                        @endforeach
                                        @if(count($metaList) > 4)
                                            <span class="text-[10px] text-indigo-600 font-bold self-center">+{{ count($metaList) - 4 }} más</span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="py-3.5 px-5 text-center whitespace-nowrap">
                                @if($procedure->device)
                                    <span class="bg-slate-800 text-white py-1 px-3 rounded-lg text-xs font-bold shadow-xs inline-block">
                                        🚓 {{ $procedure->device->name }}
                                    </span>
                                    @if($procedure->mobile_arrived_at && !$procedure->arrived_at)
                                        <div class="mt-1">
                                            <span class="bg-amber-100 text-amber-900 border border-amber-300 text-[10px] font-black px-2 py-0.5 rounded-full animate-pulse inline-block">
                                                📡 GPS Móvil: Validar
                                            </span>
                                        </div>
                                    @endif
                                @else
                                    <span class="bg-red-50 text-red-600 border border-red-200 px-2.5 py-1 rounded-lg text-xs font-black">
                                        🚨 Sin Móvil
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-5 text-center whitespace-nowrap">
                                @php
                                    $colorClass = match($procedure->status) {
                                        'Pendiente' => 'bg-red-100 text-red-800 border-red-200',
                                        'En Camino' => 'bg-amber-100 text-amber-900 border-amber-300',
                                        'En el Lugar' => 'bg-blue-100 text-blue-800 border-blue-200',
                                        'Derivado' => 'bg-orange-100 text-orange-800 border-orange-200',
                                        default => 'bg-slate-100 text-slate-700 border-slate-200',
                                    };
                                @endphp
                                <span class="{{ $colorClass }} border py-1 px-3 rounded-full text-xs font-black">
                                    {{ $procedure->status }}
                                </span>
                            </td>
                            <td class="py-3.5 px-5 text-center whitespace-nowrap">
                                @php $sla = $procedure->sla_traffic_light; @endphp
                                <span class="inline-flex items-center gap-1.5 border py-1 px-2.5 rounded-full text-xs font-bold {{ $sla['badge'] }}">
                                    <span class="w-2 h-2 rounded-full {{ $sla['dot'] }}"></span>
                                    {{ $sla['label'] }}
                                </span>
                            </td>
                            <td class="py-3.5 px-5 text-center whitespace-nowrap">
                                <a href="{{ route('procedures.show', $procedure->id) }}" wire:navigate class="bg-blue-600 hover:bg-blue-700 text-white py-1.5 px-3.5 rounded-lg text-xs font-black shadow-xs transition">
                                    Operar →
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 px-6 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <svg class="w-14 h-14 mb-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <p class="text-base font-black text-slate-600">No hay procedimientos activos para los filtros seleccionados</p>
                                    <p class="text-xs text-slate-400 mt-0.5">La central se mantiene en monitoreo preventivo.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>