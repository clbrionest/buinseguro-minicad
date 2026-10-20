<div wire:poll.10s class="space-y-5">

    {{-- BANNER DE CONTINGENCIA / EVENTO MASIVO ACTIVO --}}
    @if($activeEvent)
        <div class="bg-[#DC3545] rounded-lg shadow-sm p-4 text-brand-white flex flex-col md:flex-row justify-between items-center gap-4 border border-white/20" role="region" aria-label="Contingencia activa en curso">
            <div class="flex items-center gap-3">
                <span class="w-11 h-11 bg-black/20 rounded-lg flex items-center justify-center text-xl shrink-0">
                    <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                </span>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-extrabold uppercase tracking-wider bg-black/30 px-2 py-0.5 rounded">{{ $activeEvent->event_type }} EN CURSO</span>
                        <span class="text-xs font-mono cad-tabular opacity-90">Iniciado {{ $activeEvent->created_at->format('d/m H:i') }}</span>
                    </div>
                    <h3 class="text-lg font-extrabold mt-0.5">{{ $activeEvent->name }}</h3>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-4">
                <div class="flex gap-3 text-center bg-black/20 px-4 py-2 rounded-lg font-mono cad-tabular">
                    <div>
                        <span class="block text-xl font-black">{{ $activeEvent->reports_count }}</span>
                        <span class="text-[10px] font-sans uppercase font-bold opacity-85">Reportes</span>
                    </div>
                    <div class="border-l border-white/30 pl-3">
                        <span class="block text-xl font-black text-brand-lime-light">{{ $activeEvent->pending_reports_count }}</span>
                        <span class="text-[10px] font-sans uppercase font-bold opacity-85">Pendientes</span>
                    </div>
                    <div class="border-l border-white/30 pl-3">
                        <span class="block text-xl font-black text-white">{{ $activeEvent->in_progress_reports_count }}</span>
                        <span class="text-[10px] font-sans uppercase font-bold opacity-85">En Atención</span>
                    </div>
                </div>
                <a href="{{ route('events.manage') }}" class="bg-brand-lime hover:bg-brand-lime-mid text-brand-black font-extrabold py-2.5 px-4 rounded-lg shadow-xs text-xs whitespace-nowrap transition inline-flex items-center gap-1.5">
                    <i class="fa-solid fa-bolt" aria-hidden="true"></i>
                    <span>Operar Evento Masivo →</span>
                </a>
            </div>
        </div>
    @endif

    {{-- BARRA DE ESTADO DE TURNO DE SALA (APERTURA / CONSIGNAS / CIERRE) --}}
    @hasanyrole('Administrador|Supervisor|Operador')
        @if($activeShift)
            <div class="bg-ui-sidebar border border-white/15 rounded-lg px-4 py-2.5 text-brand-white flex flex-col md:flex-row justify-between items-start md:items-center gap-3 shadow-xs">
                <div class="flex flex-wrap items-center gap-3 text-xs">
                    <span class="inline-flex items-center gap-1.5 bg-brand-lime text-brand-black font-extrabold px-2.5 py-1 rounded-md uppercase">
                        <i class="fa-solid fa-tower-broadcast" aria-hidden="true"></i> {{ $activeShift->shift_type }} Activo
                    </span>
                    <span class="text-brand-white/90">
                        <i class="fa-solid fa-user-check text-brand-lime mr-1" aria-hidden="true"></i> Operador a cargo: <strong class="text-brand-white">{{ $activeShift->openedBy->name ?? 'N/A' }}</strong>
                    </span>
                    <span class="text-brand-cobalt-light font-mono cad-tabular">
                        <i class="fa-regular fa-clock mr-1" aria-hidden="true"></i> Aperturado {{ $activeShift->started_at->format('d/m H:i') }} hrs
                    </span>
                    @if($lastClosedShift && $lastClosedShift->handover_notes)
                        <span class="bg-white/10 border border-white/20 text-brand-lime-light px-2.5 py-0.5 rounded-md font-semibold truncate max-w-md" title="{{ $lastClosedShift->handover_notes }}">
                            <i class="fa-solid fa-triangle-exclamation text-brand-lime mr-1" aria-hidden="true"></i> Consigna guardia anterior: {{ \Illuminate\Support\Str::limit($lastClosedShift->handover_notes, 65) }}
                        </span>
                    @endif
                </div>
                <a href="{{ route('shifts.manage') }}" class="text-xs font-extrabold bg-brand-lime hover:bg-brand-lime-mid text-brand-black px-3 py-1.5 rounded-lg inline-flex items-center gap-1.5 transition shrink-0">
                    <i class="fa-solid fa-clipboard-check" aria-hidden="true"></i> Bitácora / Entrega de Turno
                </a>
            </div>
        @else
            <div class="bg-ui-card border border-ui-border border-l-4 border-l-priority-high rounded-lg px-4 py-3 flex flex-col md:flex-row justify-between items-start md:items-center gap-3 shadow-xs">
                <div class="text-xs text-ui-fg">
                    <div class="font-extrabold uppercase flex items-center gap-1.5 text-priority-high">
                        <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> Sin Turno de Sala Abierto en este momento
                    </div>
                    <p class="mt-0.5 text-ui-muted">
                        Recuerde realizar la <strong class="text-ui-fg">Recepción y Apertura de Guardia</strong> al iniciar su turno para registrar novedades y habilitar el envío del reporte ejecutivo al cierre.
                        @if($lastClosedShift && $lastClosedShift->handover_notes)
                            <span class="block mt-1 font-bold text-ui-fg">📌 Consigna dejada por {{ $lastClosedShift->closedBy->name ?? $lastClosedShift->openedBy->name ?? 'turno saliente' }}: "{{ $lastClosedShift->handover_notes }}"</span>
                        @endif
                    </p>
                </div>
                <x-cad.button variant="primary" :href="route('shifts.manage')">
                    <i class="fa-solid fa-play" aria-hidden="true"></i>
                    <span>Abrir / Recibir Turno Ahora</span>
                </x-cad.button>
            </div>
        @endif
    @endhasanyrole

    {{-- CABECERA DE CONSOLA CAD --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-ui-fg tracking-tight flex flex-wrap items-center gap-2.5">
                <span class="w-8 h-8 rounded-lg bg-brand-cobalt text-brand-lime inline-flex items-center justify-center text-sm shadow-xs">
                    <i class="fa-solid fa-satellite-dish" aria-hidden="true"></i>
                </span>
                <span>Central de Comunicaciones y Despacho CAD</span>
                <span class="inline-flex items-center gap-1.5 text-[11px] font-bold bg-[#16835D]/15 text-[#11684A] dark:bg-[#16835D]/25 dark:text-[#6EE7B7] border border-[#16835D]/30 px-2.5 py-0.5 rounded-md font-mono cad-tabular">
                    <span class="w-2 h-2 rounded-full bg-[#16835D]"></span> EN LÍNEA · 10s
                </span>
            </h1>
            <p class="text-xs text-ui-muted mt-1">Monitoreo operativo de procedimientos activos, disponibilidad de flota QAP, tiempos SLA y antecedentes en terreno.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <x-cad.button variant="secondary" :href="route('field.inspector')">
                <i class="fa-solid fa-mobile-screen-button text-brand-cobalt dark:text-brand-cobalt-mid" aria-hidden="true"></i>
                <span>Consola Terreno</span>
            </x-cad.button>
            @hasanyrole('Administrador|Supervisor|Operador')
            <x-cad.button variant="secondary" :href="route('events.manage')">
                <i class="fa-solid fa-cloud-showers-heavy text-priority-high" aria-hidden="true"></i>
                <span>Contingencias</span>
            </x-cad.button>
            @endhasanyrole
            <livewire:create-procedure />
        </div>
    </div>

    {{-- TARJETAS KPI INTERACTIVAS (CON NÚMEROS TABULARES Y SEMÁNTICA SEPARADA) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-cad.kpi-card
            label="Sin Despachar (Sala)"
            :value="$pendientes"
            :tone="$pendientes > 0 ? 'critical' : 'neutral'"
            icon="fa-solid fa-clock"
            :interactive="true"
            :active="$filterStatus === 'Pendiente'"
            :period="$filterStatus === 'Pendiente' ? '✓ Filtro activo (clic para quitar)' : 'Clic para filtrar cola pendiente'"
            wire:click="toggleStatusFilter('Pendiente')"
        />

        <x-cad.kpi-card
            label="Móvil en Ruta"
            :value="$enCamino"
            tone="cobalt"
            icon="fa-solid fa-route"
            :interactive="true"
            :active="$filterStatus === 'En Camino'"
            :period="$filterStatus === 'En Camino' ? '✓ Filtro activo (clic para quitar)' : 'Unidades despachadas en traslado'"
            wire:click="toggleStatusFilter('En Camino')"
        />

        <x-cad.kpi-card
            label="Móvil en el Lugar"
            :value="$enElLugar"
            tone="progress"
            icon="fa-solid fa-location-crosshairs"
            :interactive="true"
            :active="$filterStatus === 'En el Lugar'"
            :period="$filterStatus === 'En el Lugar' ? '✓ Filtro activo (clic para quitar)' : 'Procedimientos en curso en terreno'"
            wire:click="toggleStatusFilter('En el Lugar')"
        />

        @php
            $slaTone = is_null($avgArrivalMinutes)
                ? 'cobalt'
                : ($avgArrivalMinutes <= 12 ? 'completed' : ($avgArrivalMinutes <= 20 ? 'warning' : 'critical'));
        @endphp
        <x-cad.kpi-card
            label="SLA Arribo Promedio (Hoy)"
            :value="!is_null($avgArrivalMinutes) ? $avgArrivalMinutes : 'S/D'"
            :unit="!is_null($avgArrivalMinutes) ? 'min' : null"
            :tone="$slaTone"
            icon="fa-solid fa-gauge-high"
            :period="'Despacho prom. hoy: ' . (!is_null($avgDispatchMinutes) ? $avgDispatchMinutes . ' min' : 'S/D')"
        />
    </div>

    {{-- MONITOR DE ESTADO DE FLOTA (QAP) --}}
    @if(isset($fleetDevices) && $fleetDevices->count() > 0)
        <div class="rounded-lg border border-ui-border bg-ui-card p-4 text-ui-fg shadow-xs" x-data="{ openFleet: true }">
            <div class="flex flex-wrap justify-between items-center gap-2">
                <div class="flex flex-wrap items-center gap-2.5">
                    <h2 class="text-xs font-extrabold uppercase text-ui-fg tracking-wider flex items-center gap-1.5">
                        <i class="fa-solid fa-car-on text-brand-cobalt dark:text-brand-cobalt-mid" aria-hidden="true"></i>
                        <span>Monitor de Recursos y Flota Operativa (QAP)</span>
                    </h2>
                    <span class="text-xs bg-[#16835D]/15 text-[#11684A] dark:bg-[#16835D]/25 dark:text-[#6EE7B7] border border-[#16835D]/30 px-2.5 py-0.5 rounded-md font-bold font-mono cad-tabular">
                        <i class="fa-solid fa-circle-check mr-1 text-[10px]" aria-hidden="true"></i>{{ $fleetDevices->filter(fn($d) => $d->effective_status === 'Disponible (QAP)')->count() }} Disponibles
                    </span>
                    <span class="text-xs bg-[#2563EB]/15 text-[#1D4ED8] dark:bg-[#2563EB]/25 dark:text-[#93C5FD] border border-[#2563EB]/30 px-2.5 py-0.5 rounded-md font-bold font-mono cad-tabular">
                        <i class="fa-solid fa-car-on mr-1 text-[10px]" aria-hidden="true"></i>{{ $fleetDevices->filter(fn($d) => $d->effective_status === 'En Procedimiento')->count() }} En Procedimiento
                    </span>
                </div>
                <button type="button" @click="openFleet = !openFleet" class="text-xs font-bold text-ui-muted hover:text-ui-fg px-2 py-1 rounded hover:bg-ui-subtle transition">
                    <span x-show="openFleet"><i class="fa-solid fa-chevron-up mr-1" aria-hidden="true"></i>Compactar</span>
                    <span x-show="!openFleet" style="display: none;"><i class="fa-solid fa-chevron-down mr-1" aria-hidden="true"></i>Desplegar Flota</span>
                </button>
            </div>

            <div x-show="openFleet" class="mt-3 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                @foreach($fleetDevices as $dev)
                    @php
                        $effStatus = $dev->effective_status;
                        $leftBorder = match($effStatus) {
                            'Disponible (QAP)' => 'border-l-[#16835D]',
                            'En Procedimiento' => 'border-l-[#2563EB]',
                            'En Colación'      => 'border-l-[#B98500]',
                            'Punto Fijo'       => 'border-l-brand-cobalt dark:border-l-brand-cobalt-mid',
                            'En Mantención'    => 'border-l-[#DC3545]',
                            default            => 'border-l-[#64748B]',
                        };
                    @endphp
                    <div class="border border-ui-border border-l-4 {{ $leftBorder }} rounded-lg bg-ui-subtle/60 p-3 flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-start gap-1.5">
                                <span class="font-bold text-ui-fg text-xs flex items-center gap-1.5">
                                    <i class="fa-solid fa-shield-halved text-brand-cobalt dark:text-brand-cobalt-mid text-[11px]" aria-hidden="true"></i>
                                    <span>{{ $dev->name }}</span>
                                </span>
                                <x-cad.unit-status :status="$effStatus" :activeCount="$dev->active_procedures_count" />
                            </div>
                            @if($dev->activePatrolAssignment)
                                <div class="text-[11px] text-ui-muted mt-1.5 truncate">
                                    <span class="font-bold text-ui-fg">{{ $dev->activePatrolAssignment->sector->name ?? 'Sin sector' }}</span>
                                    @if($dev->activePatrolAssignment->crew->count() > 0)
                                        · {{ $dev->activePatrolAssignment->crew->pluck('name')->join(', ') }}
                                    @endif
                                </div>
                            @else
                                <div class="text-[11px] text-ui-muted italic mt-1.5">Sin turno activo asignado</div>
                            @endif
                        </div>

                        <div class="mt-2.5 pt-2 border-t border-ui-border flex justify-between items-center gap-2">
                            <label class="sr-only" for="fleet-status-{{ $dev->id }}">Estado operativo de {{ $dev->name }}</label>
                            <select id="fleet-status-{{ $dev->id }}"
                                    wire:change="updateDeviceStatus({{ $dev->id }}, $event.target.value)"
                                    class="text-[11px] py-1 px-2 rounded-md border-ui-border bg-ui-card text-ui-fg font-semibold w-full focus:border-ui-focus focus:ring-ui-focus">
                                @foreach(['Disponible (QAP)', 'En Colación', 'Punto Fijo', 'En Mantención'] as $st)
                                    <option value="{{ $st }}" @selected(($dev->operational_status ?: 'Disponible (QAP)') === $st)>
                                        Estado: {{ $st }}
                                    </option>
                                @endforeach
                            </select>
                            @if($dev->last_location_at)
                                <span class="text-[10px] text-[#16835D] dark:text-[#6EE7B7] font-mono cad-tabular font-bold whitespace-nowrap" title="Último GPS: {{ $dev->last_location_at->format('H:i:s') }}">
                                    <i class="fa-solid fa-satellite-dish mr-0.5" aria-hidden="true"></i>{{ $dev->last_location_at->diffForHumans(null, true, true) }}
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- TABLA PRINCIPAL DE CASOS ACTIVOS CON BUSCADOR Y FILTROS RÁPIDOS --}}
    <div class="bg-ui-card shadow-xs rounded-lg overflow-hidden border border-ui-border">
        <div class="bg-ui-sidebar px-5 py-3.5 border-b border-white/10 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-3">
            <div class="flex items-center gap-3">
                <h2 class="text-brand-white font-extrabold text-sm uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-list-check text-brand-lime" aria-hidden="true"></i>
                    <span>Cola Operativa de Procedimientos Activos</span>
                </h2>
                <span class="bg-brand-lime text-brand-black text-xs px-2.5 py-0.5 rounded-md font-extrabold font-mono cad-tabular">
                    {{ $procedures->count() }} Casos
                </span>
            </div>

            <div class="flex flex-wrap items-center gap-2.5 w-full lg:w-auto">
                <div class="relative flex-1 sm:w-64">
                    <label for="cad-queue-search" class="sr-only">Buscar procedimiento activo</label>
                    <i class="fa-solid fa-magnifying-glass text-white/60 text-xs absolute left-3 top-2.5 pointer-events-none" aria-hidden="true"></i>
                    <input id="cad-queue-search"
                           type="text"
                           wire:model.live.debounce.300ms="search"
                           placeholder="Buscar #Folio, calle, PPU, vecino..."
                           class="w-full bg-black/25 text-brand-white placeholder-white/60 border border-white/20 rounded-lg pl-8 pr-3 py-1.5 text-xs focus:ring-2 focus:ring-brand-lime focus:border-brand-lime">
                </div>

                <div>
                    <label for="cad-queue-sector" class="sr-only">Filtrar por sector</label>
                    <select id="cad-queue-sector"
                            wire:model.live="filterSector"
                            class="bg-black/25 text-brand-white border border-white/20 rounded-lg px-2.5 py-1.5 text-xs font-semibold focus:ring-2 focus:ring-brand-lime">
                        <option value="" class="text-slate-900">Todos los Sectores</option>
                        @foreach($sectors as $sec)
                            <option value="{{ $sec->id }}" class="text-slate-900">{{ $sec->name }}</option>
                        @endforeach
                    </select>
                </div>

                @if($search || $filterStatus || $filterSector)
                    <button type="button"
                            wire:click="$set('search', ''); $set('filterStatus', ''); $set('filterSector', '')"
                            class="text-xs bg-priority-critical hover:opacity-90 text-brand-white font-bold px-2.5 py-1.5 rounded-lg transition inline-flex items-center gap-1">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        <span>Limpiar filtros</span>
                    </button>
                @endif
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full leading-normal">
                <thead>
                    <tr class="bg-ui-subtle text-ui-muted uppercase text-[11px] tracking-wider font-extrabold border-b border-ui-border">
                        <th class="py-3 px-4 text-left">Folio / Hora</th>
                        <th class="py-3 px-4 text-left">Sector y Ubicación</th>
                        <th class="py-3 px-4 text-left">Categorización y Antecedentes</th>
                        <th class="py-3 px-4 text-center">Unidad Asignada</th>
                        <th class="py-3 px-4 text-center">Estado Procedimiento</th>
                        <th class="py-3 px-4 text-center">Prioridad / SLA</th>
                        <th class="py-3 px-4 text-center">Acción</th>
                    </tr>
                </thead>
                <tbody class="text-ui-fg text-sm divide-y divide-ui-border">
                    @forelse ($procedures as $procedure)
                        <tr class="hover:bg-ui-subtle/70 transition">
                            <td class="py-3.5 px-4 text-left whitespace-nowrap">
                                <span class="font-extrabold font-mono cad-tabular text-brand-cobalt dark:text-brand-cobalt-light text-sm">#{{ $procedure->id }}</span>
                                <div class="text-xs font-mono cad-tabular font-semibold text-ui-muted">{{ $procedure->created_at->format('H:i') }} hrs</div>
                            </td>
                            <td class="py-3.5 px-4 text-left">
                                <div class="font-bold text-ui-fg">{{ $procedure->sector->name ?? 'N/A' }}</div>
                                <div class="text-xs text-ui-muted font-medium">{{ $procedure->exact_address }}</div>
                                @if($procedure->caller_name || $procedure->caller_phone)
                                    <div class="text-[11px] text-ui-muted mt-0.5 flex items-center gap-1">
                                        <i class="fa-regular fa-user text-[10px]" aria-hidden="true"></i>
                                        <span>{{ $procedure->caller_name ?: 'Vecino' }} {{ $procedure->caller_phone ? "({$procedure->caller_phone})" : '' }}</span>
                                    </div>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-left">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="font-bold text-ui-fg">
                                        {{ $procedure->procedureType->name ?? 'N/A' }}
                                    </span>
                                    @if($procedure->initial_procedure_type_id && $procedure->initial_procedure_type_id != $procedure->procedure_type_id)
                                        <span class="text-[10px] bg-priority-high/15 text-[#B86308] dark:text-[#F8B86E] border border-priority-high/35 px-1.5 py-0.5 rounded font-bold" title="Ingresó inicialmente como: {{ $procedure->initialProcedureType->name ?? '' }}">
                                            Inicial: {{ $procedure->initialProcedureType->name ?? '' }}
                                        </span>
                                    @endif
                                </div>

                                {{-- Píldoras de Metadatos Específicos --}}
                                @php $metaList = $procedure->formatted_metadata; @endphp
                                @if(count($metaList) > 0)
                                    <div class="flex flex-wrap gap-1 mt-1.5">
                                        @foreach(array_slice($metaList, 0, 4) as $m)
                                            <span class="text-[11px] bg-ui-subtle text-ui-fg border border-ui-border px-2 py-0.5 rounded font-semibold">
                                                <strong class="text-ui-muted">{{ $m['label'] }}:</strong> {{ $m['value'] }}
                                            </span>
                                        @endforeach
                                        @if(count($metaList) > 4)
                                            <span class="text-[10px] text-brand-cobalt dark:text-brand-cobalt-light font-bold self-center">+{{ count($metaList) - 4 }} más</span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                @if($procedure->device)
                                    <span class="bg-brand-cobalt text-brand-white dark:bg-brand-cobalt-mid dark:text-brand-black py-1 px-2.5 rounded-md text-xs font-bold shadow-xs inline-flex items-center gap-1.5">
                                        <i class="fa-solid fa-car-on text-[11px]" aria-hidden="true"></i>
                                        <span>{{ $procedure->device->name }}</span>
                                    </span>
                                    @if($procedure->mobile_arrived_at && !$procedure->arrived_at)
                                        <div class="mt-1">
                                            <span class="bg-priority-high/20 text-[#B86308] dark:text-[#F8B86E] border border-priority-high/40 text-[10px] font-extrabold px-2 py-0.5 rounded-md inline-flex items-center gap-1">
                                                <i class="fa-solid fa-satellite-dish" aria-hidden="true"></i> Validar GPS
                                            </span>
                                        </div>
                                    @endif
                                @else
                                    <span class="bg-ui-subtle text-ui-muted border border-ui-border px-2.5 py-1 rounded-md text-xs font-bold inline-flex items-center gap-1">
                                        <i class="fa-solid fa-circle-minus text-[10px]" aria-hidden="true"></i>
                                        <span>Sin Unidad</span>
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                {{-- Estado del Procedimiento (Ciclo de vida operativo) --}}
                                <x-cad.procedure-status :status="$procedure->status" />
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                {{-- Prioridad / Nivel de Servicio SLA (Separado del ciclo de vida) --}}
                                <x-cad.priority-badge :sla="$procedure->sla_traffic_light" />
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <x-cad.button variant="primary" size="sm" :href="route('procedures.show', $procedure->id)" wire:navigate>
                                    <span>Operar Caso</span>
                                    <i class="fa-solid fa-arrow-right text-[10px]" aria-hidden="true"></i>
                                </x-cad.button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-6">
                                <x-cad.empty-state
                                    icon="fa-solid fa-shield-halved"
                                    title="No hay procedimientos activos para los filtros seleccionados"
                                    description="La Central de Comunicaciones y Despacho se mantiene en monitoreo preventivo."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>