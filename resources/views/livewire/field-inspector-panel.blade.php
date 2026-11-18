<div class="py-4 max-w-3xl mx-auto px-3 sm:px-6" wire:poll.10s
     x-data="{
         sendingGps: false,
         arrivingCaseId: null,
         addingPointGps: false,
         autoGpsEnabled: true,
         watchId: null,
         heartbeatTimer: null,
         lastLat: null,
         lastLng: null,
         lastSpeed: null,
         gpsAccuracy: null,
         lastSentTimestamp: 0,
         wakeLock: null,
         deferredPwaPrompt: null,
         canInstallPwa: false,
         isPwaStandalone: false,

         init() {
             this.isPwaStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

             window.addEventListener('beforeinstallprompt', (e) => {
                 e.preventDefault();
                 this.deferredPwaPrompt = e;
                 this.canInstallPwa = true;
             });

             this.requestWakeLock();
             document.addEventListener('visibilitychange', () => {
                 if (document.visibilityState === 'visible') {
                     this.requestWakeLock();
                     if (this.autoGpsEnabled && this.lastLat && this.lastLng) {
                         this.transmitSilentGps(this.lastLat, this.lastLng, this.lastSpeed);
                     }
                 }
             });

             if (this.autoGpsEnabled) {
                 this.startLiveGps();
             }
         },

         async requestWakeLock() {
             try {
                 if ('wakeLock' in navigator && !this.wakeLock) {
                     this.wakeLock = await navigator.wakeLock.request('screen');
                     this.wakeLock.addEventListener('release', () => {
                         this.wakeLock = null;
                     });
                 }
             } catch (_) {}
         },

         async installPwa() {
             if (!this.deferredPwaPrompt) return;
             this.deferredPwaPrompt.prompt();
             const { outcome } = await this.deferredPwaPrompt.userChoice;
             if (outcome === 'accepted') {
                 this.canInstallPwa = false;
                 this.deferredPwaPrompt = null;
             }
         },

         haversineMeters(lat1, lon1, lat2, lon2) {
             const R = 6371000;
             const dLat = (lat2 - lat1) * Math.PI / 180;
             const dLon = (lon2 - lon1) * Math.PI / 180;
             const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                       Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                       Math.sin(dLon / 2) * Math.sin(dLon / 2);
             return Math.round(R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)));
         },

         transmitSilentGps(lat, lng, speed = null) {
             if (!this.autoGpsEnabled || !lat || !lng) return;
             this.lastSentTimestamp = Date.now();
             $wire.updateDeviceGps(lat, lng, speed, true);
         },

         startLiveGps() {
             if (!navigator.geolocation) return;
             this.stopLiveGps();

             this.watchId = navigator.geolocation.watchPosition(
                 (pos) => {
                     const lat = pos.coords.latitude;
                     const lng = pos.coords.longitude;
                     const speed = pos.coords.speed ? Math.round(pos.coords.speed * 3.6) : null;
                     this.gpsAccuracy = pos.coords.accuracy ? Math.round(pos.coords.accuracy) : null;

                     const now = Date.now();
                     const elapsed = now - this.lastSentTimestamp;
                     const moved = (this.lastLat && this.lastLng)
                         ? this.haversineMeters(this.lastLat, this.lastLng, lat, lng)
                         : 999;

                     this.lastLat = lat;
                     this.lastLng = lng;
                     this.lastSpeed = speed;

                     // Transmitir inmediatamente en la primera lectura, si se desplazó >= 8m, o cada 12 segundos
                     if (this.lastSentTimestamp === 0 || (moved >= 8 && elapsed >= 4000) || elapsed >= 12000) {
                         this.transmitSilentGps(lat, lng, speed);
                     }
                 },
                 () => {},
                 { enableHighAccuracy: true, maximumAge: 5000, timeout: 10000 }
             );

             this.heartbeatTimer = setInterval(() => {
                 if (this.autoGpsEnabled && this.lastLat && this.lastLng && (Date.now() - this.lastSentTimestamp >= 12000)) {
                     this.transmitSilentGps(this.lastLat, this.lastLng, this.lastSpeed);
                 }
             }, 12000);
         },

         stopLiveGps() {
             if (this.watchId !== null && navigator.geolocation) {
                 navigator.geolocation.clearWatch(this.watchId);
                 this.watchId = null;
             }
             if (this.heartbeatTimer) {
                 clearInterval(this.heartbeatTimer);
                 this.heartbeatTimer = null;
             }
         },

         toggleAutoGps() {
             this.autoGpsEnabled = !this.autoGpsEnabled;
             if (this.autoGpsEnabled) {
                 this.startLiveGps();
             } else {
                 this.stopLiveGps();
             }
         },

         sendUnitGps() {
             if (!navigator.geolocation) {
                 alert('Este dispositivo no soporta geolocalización en el navegador.');
                 return;
             }
             this.sendingGps = true;
             navigator.geolocation.getCurrentPosition(
                 (pos) => {
                     this.sendingGps = false;
                     this.lastLat = pos.coords.latitude;
                     this.lastLng = pos.coords.longitude;
                     this.gpsAccuracy = pos.coords.accuracy ? Math.round(pos.coords.accuracy) : null;
                     this.lastSentTimestamp = Date.now();
                     $wire.updateDeviceGps(pos.coords.latitude, pos.coords.longitude, pos.coords.speed ? Math.round(pos.coords.speed * 3.6) : null, false);
                 },
                 (err) => {
                     this.sendingGps = false;
                     alert('No fue posible obtener la señal GPS. Verifique permisos de ubicación.');
                 },
                 { enableHighAccuracy: true, timeout: 8000 }
             );
         },

         submitAdditionalPatrolPoint() {
             this.addingPointGps = true;
             if (this.lastLat && this.lastLng) {
                 this.addingPointGps = false;
                 $wire.addAdditionalPatrolCheckpoint(this.lastLat, this.lastLng);
                 return;
             }
             if (!navigator.geolocation) {
                 this.addingPointGps = false;
                 $wire.addAdditionalPatrolCheckpoint(null, null);
                 return;
             }
             navigator.geolocation.getCurrentPosition(
                 (pos) => {
                     this.addingPointGps = false;
                     this.lastLat = pos.coords.latitude;
                     this.lastLng = pos.coords.longitude;
                     $wire.addAdditionalPatrolCheckpoint(pos.coords.latitude, pos.coords.longitude);
                 },
                 () => {
                     this.addingPointGps = false;
                     $wire.addAdditionalPatrolCheckpoint(null, null);
                 },
                 { enableHighAccuracy: true, timeout: 6000 }
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

    {{-- ENCABEZADO SHELL TERRENO (MOBILE-FIRST, PWA + CONTROLES TÁCTILES >= 44px) --}}
    <div class="bg-ui-sidebar text-brand-white rounded-xl shadow-md p-4 mb-4 border border-white/10">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-[11px] font-extrabold uppercase tracking-widest text-brand-lime-light block">SHELL TERRENO · INSPECTOR MUNICIPAL</span>
                    <span x-show="isPwaStandalone" style="display: none;" class="bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 text-[10px] font-black px-2 py-0.2 rounded-full">
                        PWA ACTIVA
                    </span>
                </div>
                <h1 class="text-xl font-extrabold flex items-center gap-2">
                    <i class="fa-solid fa-car-on text-brand-lime" aria-hidden="true"></i>
                    <span>Consola Móvil Operativa</span>
                </h1>
            </div>

            <div class="w-full sm:w-auto flex flex-wrap items-center gap-2">
                <button type="button"
                        x-show="canInstallPwa && !isPwaStandalone"
                        style="display: none;"
                        @click="installPwa()"
                        class="min-h-[44px] bg-indigo-600 hover:bg-indigo-500 text-white font-extrabold text-xs px-3 py-2 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 border border-indigo-400/40">
                    <i class="fa-solid fa-mobile-screen-button"></i>
                    <span>Instalar App (PWA)</span>
                </button>

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

        {{-- Barra de Telemetría GPS Continua en Tiempo Real --}}
        <div class="mt-3 p-2.5 rounded-lg bg-black/30 border border-white/10 flex flex-wrap items-center justify-between gap-2 text-xs">
            <div class="flex items-center gap-2">
                <span class="relative flex h-2.5 w-2.5" x-show="autoGpsEnabled">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                </span>
                <span class="h-2.5 w-2.5 rounded-full bg-slate-500" x-show="!autoGpsEnabled" style="display: none;"></span>
                <span class="font-bold text-white" x-text="autoGpsEnabled ? 'Tracking GPS Continuo Activo (Tiempo Real)' : 'Tracking GPS Automático en Pausa'"></span>
                <template x-if="lastLat && lastLng">
                    <span class="text-[11px] font-mono text-brand-lime hidden sm:inline" x-text="`(${lastLat.toFixed(5)}, ${lastLng.toFixed(5)}${gpsAccuracy ? ' · ±' + gpsAccuracy + 'm' : ''})`"></span>
                </template>
            </div>
            <button type="button"
                    @click="toggleAutoGps()"
                    class="px-2.5 py-1 rounded text-[11px] font-extrabold transition border"
                    :class="autoGpsEnabled ? 'bg-emerald-500/20 text-emerald-300 border-emerald-400/40 hover:bg-emerald-500/30' : 'bg-white/10 text-slate-300 border-white/20 hover:bg-white/20'">
                <span x-text="autoGpsEnabled ? 'Auto-GPS: ON' : 'Auto-GPS: OFF'"></span>
            </button>
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

            {{-- BOTONES TÁCTILES DE ESTADO (>= 44x44px) --}}
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
                        <span>{{ str_replace(' (QAP)', '', $stName) }}</span>
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

    {{-- SECCIÓN PATRULLAJE PREVENTIVO ACTIVO (VALIDACIÓN DE HITOS EN TERRENO) --}}
    @if(isset($activePreventivePatrol) && $activePreventivePatrol)
        <div class="mb-6 bg-ui-card rounded-2xl border-2 border-brand-cobalt/40 p-4 shadow-xs">
            <div class="flex flex-wrap items-center justify-between border-b border-ui-border pb-2.5 mb-3 gap-2">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <a href="{{ route('patrols.preventive.show', $activePreventivePatrol) }}" wire:navigate class="text-xs font-black uppercase text-brand-cobalt dark:text-brand-cobalt-light hover:underline">
                        🛡️ Patrullaje Preventivo: {{ $activePreventivePatrol->folio }}
                    </a>
                    <span class="text-[11px] font-bold text-ui-muted">({{ $activePreventivePatrol->patrol_type }})</span>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-mono font-bold text-ui-fg">
                        {{ $activePreventivePatrol->validated_checkpoints_count }}/{{ $activePreventivePatrol->total_checkpoints_count }} validados
                    </span>
                    <button type="button"
                            wire:click="toggleAddPatrolPointForm"
                            class="bg-brand-cobalt hover:bg-brand-cobalt-deep text-white font-bold text-xs px-2.5 py-1 rounded-lg transition shadow-xs inline-flex items-center gap-1">
                        <i class="fa-solid fa-location-plus text-[11px]"></i>
                        <span>{{ $showAddPatrolPointForm ? 'Cancelar Punto' : '+ Agregar Punto' }}</span>
                    </button>
                    <button type="button"
                            wire:click="$dispatch('openModalWithPatrol', { patrolId: {{ $activePreventivePatrol->id }} })"
                            class="bg-red-600 hover:bg-red-700 text-white font-bold text-xs px-2.5 py-1 rounded-lg transition shadow-xs">
                        + Iniciar Caso desde Patrullaje
                    </button>
                </div>
            </div>

            {{-- Formulario para agregar punto adicional desde terreno --}}
            @if($showAddPatrolPointForm)
                <div class="mb-3 p-3.5 rounded-xl bg-ui-subtle border border-brand-cobalt/30 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-black uppercase text-brand-cobalt dark:text-brand-cobalt-light flex items-center gap-1.5">
                            <i class="fa-solid fa-map-pin"></i>
                            <span>Incorporar Punto / Hito Adicional en Terreno</span>
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                        <div>
                            <label class="block text-[11px] font-bold text-ui-muted uppercase mb-1">Hito Conocido (Opcional)</label>
                            <select wire:model.live="new_patrol_landmark_id"
                                    class="w-full rounded-lg border-ui-border bg-ui-card text-ui-fg text-xs py-2">
                                <option value="">-- Punto personalizado --</option>
                                @foreach($landmarks ?? [] as $lm)
                                    <option value="{{ $lm->id }}">{{ $lm->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-ui-muted uppercase mb-1">Nombre del Punto *</label>
                            <input type="text"
                                   wire:model="new_patrol_point_name"
                                   placeholder="Ej: Plaza Villa Estación, Cesfam..."
                                   class="w-full rounded-lg border-ui-border bg-ui-card text-ui-fg text-xs py-2">
                            @error('new_patrol_point_name')
                                <span class="text-red-500 text-[11px] block mt-0.5">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-ui-muted uppercase mb-1">Dirección / Referencia</label>
                            <input type="text"
                                   wire:model="new_patrol_point_address"
                                   placeholder="Ej: Calle o esquina de referencia..."
                                   class="w-full rounded-lg border-ui-border bg-ui-card text-ui-fg text-xs py-2">
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-2 pt-1">
                        <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-bold text-ui-fg">
                            <input type="checkbox"
                                   wire:model="validate_new_point_immediately"
                                   class="rounded border-ui-border text-emerald-600 focus:ring-emerald-500">
                            <span>Validar paso inmediatamente con mi GPS actual</span>
                        </label>

                        <div class="flex items-center gap-2">
                            <button type="button"
                                    wire:click="toggleAddPatrolPointForm"
                                    class="px-3 py-1.5 rounded-lg border border-ui-border bg-ui-card text-ui-fg text-xs font-bold">
                                Cancelar
                            </button>
                            <button type="button"
                                    @click="submitAdditionalPatrolPoint()"
                                    :disabled="addingPointGps"
                                    class="px-3.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-xs font-extrabold shadow-xs inline-flex items-center gap-1.5">
                                <i class="fa-solid fa-check" x-show="!addingPointGps"></i>
                                <i class="fa-solid fa-circle-notch fa-spin" x-show="addingPointGps" style="display: none;"></i>
                                <span>Guardar Punto Adicional</span>
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            @if($activePreventivePatrol->checkpoints->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2" x-data>
                    @foreach($activePreventivePatrol->checkpoints as $cp)
                        <div class="p-2.5 rounded-xl border text-xs flex items-center justify-between gap-2 {{ $cp->status === 'Validado' ? 'bg-emerald-500/10 border-emerald-500/30' : 'bg-ui-subtle border-ui-border' }}">
                            <div class="min-w-0 flex-1">
                                <div class="font-bold text-ui-fg truncate">{{ $cp->order }}. {{ $cp->name }}</div>
                                <div class="text-[10px] text-ui-muted truncate">{{ $cp->address }}</div>
                            </div>
                            @if($cp->status === 'Validado')
                                <span class="text-[11px] font-bold text-emerald-600 shrink-0 font-mono">✓ {{ $cp->validated_at?->format('H:i') }}</span>
                            @else
                                <button type="button"
                                        @click="
                                            if (navigator.geolocation) {
                                                navigator.geolocation.getCurrentPosition(
                                                    (pos) => $wire.validatePatrolCheckpoint({{ $cp->id }}, pos.coords.latitude, pos.coords.longitude),
                                                    () => $wire.validatePatrolCheckpoint({{ $cp->id }})
                                                );
                                            } else {
                                                $wire.validatePatrolCheckpoint({{ $cp->id }});
                                            }
                                        "
                                        class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-2 py-1 rounded text-xs shrink-0 shadow-xs">
                                    Validar Paso
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
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
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <p class="font-bold text-ui-fg flex items-start gap-1.5">
                                <i class="fa-solid fa-location-dot text-brand-cobalt dark:text-brand-cobalt-mid mt-1" aria-hidden="true"></i>
                                <span>{{ $proc->sector->name ?? 'Sector' }} — {{ $proc->exact_address }}</span>
                            </p>
                            @if($proc->latitude && $proc->longitude)
                                <span class="text-[10px] font-mono font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-500/15 border border-emerald-500/30 px-2 py-0.5 rounded-full">
                                    📍 GPS: {{ number_format((float) $proc->latitude, 5) }}, {{ number_format((float) $proc->longitude, 5) }}
                                </span>
                            @endif
                        </div>
                        @if($proc->address_reference)
                            <p class="text-xs text-ui-muted"><strong>Ref:</strong> {{ $proc->address_reference }}</p>
                        @endif
                        @if($proc->latitude && $proc->longitude)
                            <div wire:ignore
                                 wire:key="inspector-proc-map-{{ $proc->id }}"
                                 class="pt-1.5"
                                 x-data="{
                                     initFieldMap() {
                                         if (typeof google === 'undefined' || !google.maps) return;
                                         const pos = { lat: {{ (float) $proc->latitude }}, lng: {{ (float) $proc->longitude }} };
                                         const map = new google.maps.Map(this.$refs.fieldMapCanvas, {
                                             zoom: 17,
                                             center: pos,
                                             mapTypeControl: false,
                                             streetViewControl: false,
                                             fullscreenControl: false,
                                             zoomControl: true,
                                         });
                                         new google.maps.Marker({
                                             position: pos,
                                             map: map,
                                             title: @js($proc->exact_address),
                                         });
                                     }
                                 }"
                                 x-init="$nextTick(() => initFieldMap())">
                                <div x-ref="fieldMapCanvas" class="w-full h-36 rounded-lg border border-ui-border shadow-inner"></div>
                            </div>
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
