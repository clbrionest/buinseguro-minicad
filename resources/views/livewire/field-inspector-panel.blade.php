<div class="py-4 max-w-3xl mx-auto px-3 sm:px-6" wire:poll.10s
     x-data="{
         sendingGps: false,
         arrivingCaseId: null,
         sendUnitGps() {
             if (!navigator.geolocation) {
                 alert('Este dispositivo no soporta geolocalización en el navegador.');
                 return;
             }
             this.sendingGps = true;
             navigator.geolocation.getCurrentPosition(
                 (pos) => {
                     this.sendingGps = false;
                     $wire.updateDeviceGps(pos.coords.latitude, pos.coords.longitude);
                 },
                 (err) => {
                     this.sendingGps = false;
                     alert('No fue posible obtener la señal GPS. Verifique permisos de ubicación.');
                 },
                 { enableHighAccuracy: true, timeout: 8000 }
             );
         },
         markFieldArrival(caseId) {
             this.arrivingCaseId = caseId;
             if (!navigator.geolocation) {
                 this.arrivingCaseId = null;
                 $wire.confirmArrivalFromField(caseId, null, null);
                 return;
             }
             navigator.geolocation.getCurrentPosition(
                 (pos) => {
                     this.arrivingCaseId = null;
                     $wire.confirmArrivalFromField(caseId, pos.coords.latitude, pos.coords.longitude);
                 },
                 (err) => {
                     this.arrivingCaseId = null;
                     $wire.confirmArrivalFromField(caseId, null, null);
                 },
                 { enableHighAccuracy: true, timeout: 8000 }
             );
         }
     }">

    {{-- ENCABEZADO SHELL TERRENO (MOBILE-FIRST, CONTROLES TÁCTILES >= 44px) --}}
    <div class="bg-ui-sidebar text-brand-white rounded-xl shadow-md p-4 mb-4 border border-white/10">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <div>
                <span class="text-[11px] font-extrabold uppercase tracking-widest text-brand-lime-light block">SHELL TERRENO · INSPECTOR MUNICIPAL</span>
                <h1 class="text-xl font-extrabold flex items-center gap-2">
                    <i class="fa-solid fa-car-on text-brand-lime" aria-hidden="true"></i>
                    <span>Consola Móvil Operativa</span>
                </h1>
            </div>

            <div class="w-full sm:w-auto flex items-center gap-2">
                <label for="field-device-select" class="sr-only">Seleccionar unidad móvil</label>
                <select id="field-device-select"
                        wire:model.live="selected_device_id"
                        class="w-full sm:w-60 min-h-[44px] rounded-lg border-white/20 bg-black/25 text-brand-white font-bold text-sm focus:ring-2 focus:ring-brand-lime">
                    @foreach($devices as $dev)
                        <option value="{{ $dev->id }}" class="text-slate-900">
                            {{ $dev->name }} ({{ $dev->effective_status }})
                        </option>
                    @endforeach
                </select>
                <button type="button"
                        @click="sendUnitGps()"
                        :disabled="sendingGps"
                        class="min-h-[44px] bg-brand-lime hover:bg-brand-lime-mid disabled:opacity-50 text-brand-black font-extrabold text-xs px-4 py-2.5 rounded-lg shadow-xs whitespace-nowrap transition inline-flex items-center gap-1.5">
                    <span x-show="!sendingGps"><i class="fa-solid fa-satellite-dish mr-1" aria-hidden="true"></i>Enviar GPS</span>
                    <span x-show="sendingGps" style="display: none;"><i class="fa-solid fa-circle-notch fa-spin mr-1" aria-hidden="true"></i>GPS...</span>
                </button>
            </div>
        </div>

        @if($currentDevice)
            <div class="mt-3 pt-3 border-t border-white/15 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 text-xs text-brand-cobalt-light">
                <div>
                    @if($currentDevice->activePatrolAssignment)
                        Sector: <strong class="text-brand-white">{{ $currentDevice->activePatrolAssignment->sector->name ?? 'General' }}</strong>
                        · Tripulación: <strong class="text-brand-white">{{ $currentDevice->activePatrolAssignment->crew->pluck('name')->join(', ') ?: 'Sin asignar' }}</strong>
                    @else
                        <span class="text-brand-lime-light"><i class="fa-solid fa-circle-exclamation mr-1" aria-hidden="true"></i>Sin turno activo registrado en sistema para este móvil.</span>
                    @endif
                </div>
                @if($currentDevice->last_location_at)
                    <span class="text-brand-lime font-mono cad-tabular font-bold">
                        Último GPS: {{ $currentDevice->last_location_at->format('H:i:s') }}
                    </span>
                @endif
            </div>

            {{-- BOTONES TÁCTILES DE ESTADO QAP (>= 44x44px) --}}
            <div class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-2" role="group" aria-label="Cambiar estado operativo de unidad">
                @php
                    $statuses = [
                        'Disponible (QAP)' => ['icon' => 'fa-solid fa-circle-check', 'active' => 'bg-brand-lime text-brand-black ring-2 ring-white'],
                        'Punto Fijo'       => ['icon' => 'fa-solid fa-location-pin', 'active' => 'bg-brand-white text-brand-cobalt ring-2 ring-brand-lime'],
                        'En Colación'      => ['icon' => 'fa-solid fa-mug-saucer', 'active' => 'bg-[#B98500] text-brand-white ring-2 ring-white'],
                        'En Mantención'    => ['icon' => 'fa-solid fa-screwdriver-wrench', 'active' => 'bg-priority-critical text-brand-white ring-2 ring-white'],
                    ];
                    $currentBase = $currentDevice->operational_status ?: 'Disponible (QAP)';
                @endphp
                @foreach($statuses as $stName => $cfg)
                    <button type="button"
                            wire:click="setDeviceStatus('{{ $stName }}')"
                            :aria-pressed="{{ $currentBase === $stName ? 'true' : 'false' }}"
                            class="min-h-[44px] py-2.5 px-2.5 rounded-lg text-xs font-extrabold transition flex items-center justify-center gap-1.5 {{ $currentBase === $stName ? $cfg['active'] : 'bg-black/25 text-brand-white/85 hover:bg-white/15 border border-white/10' }}">
                        <i class="{{ $cfg['icon'] }}" aria-hidden="true"></i>
                        <span>{{ $stName }}</span>
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    @if (session()->has('field_message'))
        <div class="bg-[#16835D]/15 border-l-4 border-[#16835D] text-ui-fg p-3.5 mb-4 rounded-lg shadow-xs text-sm font-bold flex items-center gap-2" role="status">
            <i class="fa-solid fa-circle-check text-[#16835D]" aria-hidden="true"></i>
            <span>{{ session('field_message') }}</span>
        </div>
    @endif

    {{-- SECCIÓN 1: PROCEDIMIENTOS ASIGNADOS AL MÓVIL --}}
    <div class="mb-6">
        <div class="flex justify-between items-center mb-3">
            <h2 class="text-sm font-extrabold text-ui-fg uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-clipboard-list text-brand-cobalt dark:text-brand-cobalt-mid" aria-hidden="true"></i>
                <span>Procedimientos Asignados ({{ $assignedProcedures->count() }})</span>
            </h2>
            <span class="text-xs text-ui-muted font-mono cad-tabular">Sincronización 10s</span>
        </div>

        <div class="space-y-4">
            @forelse($assignedProcedures as $proc)
                @php
                    $sla = $proc->sla_traffic_light;
                    $navQuery = ($proc->latitude && $proc->longitude)
                        ? "{$proc->latitude},{$proc->longitude}"
                        : urlencode($proc->exact_address . ', Buin, Chile');
                @endphp
                <div class="bg-ui-card text-ui-fg rounded-xl shadow-xs border border-ui-border border-l-4 {{ $proc->status === 'En Camino' ? 'border-l-brand-cobalt dark:border-l-brand-cobalt-mid' : 'border-l-state-progress' }} p-4">
                    <div class="flex flex-wrap justify-between items-start gap-2 mb-2.5">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-xs font-extrabold font-mono cad-tabular bg-brand-cobalt text-brand-white dark:bg-brand-cobalt-mid dark:text-brand-black px-2 py-0.5 rounded">#{{ $proc->id }}</span>
                                <x-cad.procedure-status :status="$proc->status" />
                                <x-cad.priority-badge :sla="$sla" />
                            </div>
                            <h3 class="text-lg font-extrabold text-ui-fg mt-1.5">{{ $proc->procedureType->name ?? 'Procedimiento' }}</h3>
                        </div>
                        <span class="text-xs font-mono cad-tabular font-bold text-ui-muted">{{ $proc->created_at->format('H:i') }} hrs</span>
                    </div>

                    <div class="bg-ui-subtle rounded-lg p-3 border border-ui-border text-sm space-y-1.5 mb-3">
                        <p class="font-bold text-ui-fg flex items-start gap-1.5">
                            <i class="fa-solid fa-location-dot text-brand-cobalt dark:text-brand-cobalt-mid mt-1" aria-hidden="true"></i>
                            <span>{{ $proc->sector->name ?? 'Sector' }} — {{ $proc->exact_address }}</span>
                        </p>
                        @if($proc->address_reference)
                            <p class="text-xs text-ui-muted"><strong>Ref:</strong> {{ $proc->address_reference }}</p>
                        @endif
                        @if($proc->caller_name || $proc->caller_phone)
                            <p class="text-xs text-ui-muted pt-1.5 border-t border-ui-border">
                                <strong class="text-ui-fg">Informante:</strong> {{ $proc->caller_name ?: 'Anónimo' }}
                                @if($proc->caller_phone)
                                    · <a href="tel:{{ $proc->caller_phone }}" class="text-brand-cobalt dark:text-brand-cobalt-light font-bold underline font-mono">{{ $proc->caller_phone }}</a>
                                @endif
                            </p>
                        @endif
                        @php $fieldMeta = $proc->formatted_metadata; @endphp
                        @if(count($fieldMeta) > 0)
                            <div class="pt-1.5 border-t border-ui-border flex flex-wrap gap-1">
                                @foreach($fieldMeta as $fm)
                                    <span class="text-[11px] bg-ui-card text-ui-fg border border-ui-border px-2 py-0.5 rounded font-semibold">
                                        <strong class="text-ui-muted">{{ $fm['label'] }}:</strong> {{ $fm['value'] }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- ACCIONES TÁCTILES DE TERRENO (>= 44x44px) --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        {{-- 1. Navegación GPS --}}
                        <div class="flex gap-1.5">
                            <a href="https://www.google.com/maps/dir/?api=1&destination={{ $navQuery }}" target="_blank"
                               class="min-h-[44px] flex-1 bg-ui-subtle hover:bg-brand-cobalt/10 text-ui-fg border border-ui-border font-bold py-2.5 px-3 rounded-lg text-xs text-center flex items-center justify-center gap-1.5 transition">
                                <i class="fa-solid fa-map-location-dot text-brand-cobalt dark:text-brand-cobalt-mid" aria-hidden="true"></i>
                                <span>Maps</span>
                            </a>
                            @if($proc->latitude && $proc->longitude)
                                <a href="https://waze.com/ul?ll={{ $proc->latitude }},{{ $proc->longitude }}&navigate=yes" target="_blank"
                                   class="min-h-[44px] flex-1 bg-ui-subtle hover:bg-brand-cobalt/10 text-ui-fg border border-ui-border font-bold py-2.5 px-3 rounded-lg text-xs text-center flex items-center justify-center gap-1.5 transition">
                                    <i class="fa-solid fa-route text-state-progress" aria-hidden="true"></i>
                                    <span>Waze</span>
                                </a>
                            @endif
                        </div>

                        {{-- 2. Confirmación Llegada GPS --}}
                        <div>
                            @if($proc->arrived_at)
                                <div class="min-h-[44px] w-full bg-[#16835D]/15 text-[#11684A] dark:text-[#6EE7B7] border border-[#16835D]/35 font-bold py-2.5 px-3 rounded-lg text-xs flex items-center justify-center gap-1.5">
                                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                                    <span>En el Lugar ({{ $proc->arrived_at->format('H:i') }})</span>
                                </div>
                            @elseif($proc->mobile_arrived_at)
                                <div class="min-h-[44px] w-full bg-priority-high/15 text-[#B86308] dark:text-[#F8B86E] border border-priority-high/40 font-bold py-2.5 px-3 rounded-lg text-xs flex items-center justify-center gap-1.5">
                                    <i class="fa-solid fa-satellite-dish" aria-hidden="true"></i>
                                    <span>GPS Enviado ({{ $proc->mobile_arrived_at->format('H:i') }})</span>
                                </div>
                            @else
                                <button type="button"
                                        @click="markFieldArrival({{ $proc->id }})"
                                        :disabled="arrivingCaseId === {{ $proc->id }}"
                                        class="min-h-[44px] w-full bg-brand-lime hover:bg-brand-lime-mid disabled:opacity-50 text-brand-black font-extrabold py-2.5 px-3 rounded-lg shadow-xs text-xs flex items-center justify-center gap-1.5 transition">
                                    <span x-show="arrivingCaseId !== {{ $proc->id }}"><i class="fa-solid fa-location-crosshairs mr-1" aria-hidden="true"></i>Marcar Llegada (GPS)</span>
                                    <span x-show="arrivingCaseId === {{ $proc->id }}" style="display: none;"><i class="fa-solid fa-circle-notch fa-spin mr-1" aria-hidden="true"></i>Capturando GPS...</span>
                                </button>
                            @endif
                        </div>

                        {{-- 3. Novedad / Foto Rápida o Ficha Completa --}}
                        <div class="flex gap-1.5">
                            <button type="button" wire:click="openQuickNote({{ $proc->id }})"
                                    class="min-h-[44px] flex-1 bg-brand-cobalt hover:bg-brand-cobalt-deep text-brand-white dark:bg-brand-cobalt-mid dark:text-brand-black font-bold py-2.5 px-3 rounded-lg text-xs inline-flex items-center justify-center gap-1.5 transition">
                                <i class="fa-solid fa-camera" aria-hidden="true"></i>
                                <span>Nota / Foto</span>
                            </button>
                            <a href="{{ route('procedures.show', $proc->id) }}"
                               class="min-h-[44px] bg-ui-subtle hover:bg-ui-border text-ui-fg border border-ui-border font-bold py-2.5 px-3 rounded-lg text-xs flex items-center justify-center gap-1 transition">
                                <span>Ficha</span>
                                <i class="fa-solid fa-arrow-right text-[10px]" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>

                    {{-- FORMULARIO RÁPIDO DESPLEGABLE PARA FOTO / NOVEDAD EN TERRENO --}}
                    @if($active_procedure_for_note === $proc->id)
                        <form wire:submit.prevent="saveQuickNote({{ $proc->id }})" class="mt-3 pt-3 border-t border-ui-border bg-ui-subtle p-3 rounded-lg space-y-2.5">
                            <label class="block text-xs font-bold text-ui-fg uppercase">Enviar Reporte / Evidencia desde Terreno</label>
                            <textarea wire:model="quick_note" rows="2" class="w-full rounded-lg border-ui-border bg-ui-card text-ui-fg text-sm" placeholder="Escriba la novedad observada en el lugar..."></textarea>
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                                <input type="file" wire:model="quick_photo" accept="image/*" capture="environment" class="text-xs text-ui-muted file:mr-2 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-brand-cobalt/10 file:text-brand-cobalt dark:file:bg-brand-cobalt-mid/20 dark:file:text-brand-cobalt-light">
                                <div class="flex gap-2 w-full sm:w-auto justify-end">
                                    <x-cad.button type="button" variant="secondary" wire:click="openQuickNote({{ $proc->id }})">Cancelar</x-cad.button>
                                    <x-cad.button type="submit" variant="primary">Enviar a Central</x-cad.button>
                                </div>
                            </div>
                            @error('quick_note') <span class="text-priority-critical text-xs block">{{ $message }}</span> @enderror
                            @error('quick_photo') <span class="text-priority-critical text-xs block">{{ $message }}</span> @enderror
                        </form>
                    @endif
                </div>
            @empty
                <x-cad.empty-state
                    icon="fa-solid fa-car-on"
                    title="Sin procedimientos activos asignados a este móvil"
                    description="Manténgase atento a las instrucciones de Central o en patrullaje preventivo."
                />
            @endforelse
        </div>
    </div>

    {{-- SECCIÓN 2: REPORTES DE CONTINGENCIA / EVENTOS MASIVOS ASIGNADOS AL MÓVIL --}}
    @if($assignedEventReports->count() > 0)
        <div class="mb-6">
            <h2 class="text-sm font-extrabold text-priority-high uppercase tracking-wider mb-3 flex items-center gap-2">
                <i class="fa-solid fa-cloud-showers-heavy" aria-hidden="true"></i>
                <span>Reportes de Contingencia Asignados ({{ $assignedEventReports->count() }})</span>
            </h2>
            <div class="space-y-3">
                @foreach($assignedEventReports as $rep)
                    <div class="bg-ui-card border border-ui-border border-l-4 border-l-priority-high rounded-xl p-4 shadow-xs flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-extrabold bg-priority-high/20 text-[#B86308] dark:text-[#F8B86E] px-2 py-0.5 rounded">{{ $rep->quick_category }}</span>
                                <span class="text-xs font-bold text-ui-muted">{{ $rep->event->name ?? 'Contingencia' }}</span>
                            </div>
                            <p class="font-bold text-ui-fg text-sm mt-1">📍 {{ $rep->sector->name ?? '' }} — {{ $rep->exact_address }}</p>
                            @if($rep->notes)
                                <p class="text-xs text-ui-muted mt-0.5">{{ $rep->notes }}</p>
                            @endif
                        </div>
                        <button type="button" wire:click="resolveEventReportFromField({{ $rep->id }})"
                                class="min-h-[44px] w-full sm:w-auto bg-[#16835D] hover:opacity-90 text-brand-white font-bold text-xs px-4 py-2.5 rounded-lg shadow-xs whitespace-nowrap inline-flex items-center justify-center gap-1.5">
                            <i class="fa-solid fa-check" aria-hidden="true"></i>
                            <span>Marcar Resuelto</span>
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>
