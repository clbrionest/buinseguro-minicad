<div class="py-6 max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

    {{-- CABECERA Y BARRAS DE NAVEGACIÓN --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-ui-card p-5 rounded-2xl border border-ui-border shadow-xs">
        <div>
            <div class="flex items-center gap-2 text-xs text-ui-muted mb-1 font-bold">
                <a href="{{ route('patrols.preventive') }}" wire:navigate class="hover:text-brand-cobalt flex items-center gap-1">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Patrullajes Preventivos</span>
                </a>
                <span>/</span>
                <span class="font-mono text-ui-fg">{{ $patrol->folio }}</span>
            </div>
            
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="text-2xl font-black font-mono text-ui-fg">{{ $patrol->folio }}</h1>
                
                {{-- Badge de Estado --}}
                @if($patrol->status === 'En Curso')
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300 animate-pulse">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        <span>EN CURSO</span>
                    </span>
                @elseif($patrol->status === 'Completado')
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 border border-blue-300">
                        ✓ COMPLETADO
                    </span>
                @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                        {{ strtoupper($patrol->status) }}
                    </span>
                @endif

                <span class="text-xs px-2.5 py-1 rounded-lg bg-ui-subtle border border-ui-border font-bold text-ui-muted">
                    {{ $patrol->patrol_type }}
                </span>
            </div>
        </div>

        {{-- BOTONERA DE ACCIÓN OPERATIVA --}}
        <div class="flex flex-wrap items-center gap-2">
            {{-- Emitir Informe / Imprimir --}}
            <a href="{{ route('patrols.preventive.print', $patrol) }}"
               target="_blank"
               class="bg-ui-subtle hover:bg-ui-border text-ui-fg font-bold py-2 px-3.5 rounded-lg border border-ui-border text-xs inline-flex items-center gap-1.5 transition">
                <i class="fa-solid fa-file-pdf text-red-500"></i>
                <span>Emitir Informe Oficial</span>
            </a>

            {{-- Iniciar Procedimiento desde este Patrullaje --}}
            <button type="button"
                    wire:click="startProcedure"
                    class="bg-red-600 hover:bg-red-700 text-white font-black py-2 px-3.5 rounded-lg text-xs inline-flex items-center gap-1.5 transition shadow-xs">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>Iniciar Procedimiento desde este Patrullaje</span>
            </button>

            {{-- Finalizar Patrullaje --}}
            @if($patrol->status === 'En Curso')
                <button type="button"
                        wire:click="$set('showCompleteModal', true)"
                        class="bg-brand-cobalt hover:bg-brand-cobalt-deep text-white font-black py-2 px-3.5 rounded-lg text-xs inline-flex items-center gap-1.5 transition shadow-xs">
                    <i class="fa-solid fa-flag-checkered"></i>
                    <span>Finalizar Patrullaje</span>
                </button>
            @endif
        </div>
    </div>

    {{-- ALERTAS DE ACCIÓN --}}
    @if (session()->has('message'))
        <div class="p-3 bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 dark:text-emerald-300 rounded-xl text-xs font-bold flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-circle-check"></i>
                <span>{{ session('message') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">✕</button>
        </div>
    @endif

    {{-- BLOQUE SUPERIOR: FICHA TÉCNICA Y RESUMEN OPERATIVO --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
        {{-- Móvil y Tripulación --}}
        <div class="bg-ui-card p-4 rounded-xl border border-ui-border shadow-xs">
            <span class="text-[10px] font-black uppercase tracking-wider text-ui-muted block mb-1">Móvil Asignado</span>
            <div class="flex items-center gap-2 mb-2">
                <span class="text-xl">🚓</span>
                <span class="text-base font-black text-ui-fg">{{ $patrol->device?->name ?? 'Sin asignar' }}</span>
                <span class="text-[10px] px-2 py-0.5 rounded font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                    {{ $patrol->device?->effective_status }}
                </span>
            </div>
            <div class="text-xs text-ui-muted border-t border-ui-border pt-2 space-y-1">
                <div><strong>Sector:</strong> {{ $patrol->sector?->name ?? 'Comuna General' }}</div>
                <div><strong>Tripulación:</strong> {{ $patrol->patrolAssignment?->crew?->pluck('name')->join(', ') ?: 'Sin registro de tripulación' }}</div>
            </div>
        </div>

        {{-- Tiempos y Duración --}}
        <div class="bg-ui-card p-4 rounded-xl border border-ui-border shadow-xs">
            <span class="text-[10px] font-black uppercase tracking-wider text-ui-muted block mb-1">Tiempos Operativos</span>
            <div class="space-y-1 font-mono text-xs">
                <div class="flex justify-between">
                    <span class="text-ui-muted">Hora de Inicio:</span>
                    <strong class="text-ui-fg">{{ $patrol->started_at->format('d/m/Y H:i:s') }}</strong>
                </div>
                <div class="flex justify-between">
                    <span class="text-ui-muted">Hora de Fin:</span>
                    <strong class="text-ui-fg">{{ $patrol->ended_at ? $patrol->ended_at->format('d/m/Y H:i:s') : 'En transcurso' }}</strong>
                </div>
                <div class="flex justify-between border-t border-ui-border pt-1 text-brand-cobalt dark:text-brand-cobalt-light font-black">
                    <span>Duración Total:</span>
                    <span>⏱ {{ $patrol->duration_formatted }}</span>
                </div>
            </div>
        </div>

        {{-- Odómetro y Kilometraje --}}
        <div class="bg-ui-card p-4 rounded-xl border border-ui-border shadow-xs">
            <span class="text-[10px] font-black uppercase tracking-wider text-ui-muted block mb-1">Control de Kilometraje</span>
            <div class="space-y-1 font-mono text-xs">
                <div class="flex justify-between">
                    <span class="text-ui-muted">Odómetro Inicial:</span>
                    <strong class="text-ui-fg">{{ $patrol->start_odometer ? number_format($patrol->start_odometer, 0, ',', '.').' km' : 'No registrado' }}</strong>
                </div>
                <div class="flex justify-between">
                    <span class="text-ui-muted">Odómetro Final:</span>
                    <strong class="text-ui-fg">{{ $patrol->end_odometer ? number_format($patrol->end_odometer, 0, ',', '.').' km' : 'Pendiente' }}</strong>
                </div>
                @if($patrol->start_odometer && $patrol->end_odometer)
                    <div class="flex justify-between border-t border-ui-border pt-1 text-emerald-600 font-black">
                        <span>Recorrido:</span>
                        <span>{{ number_format($patrol->end_odometer - $patrol->start_odometer, 0, ',', '.') }} km</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Porcentaje de Cumplimiento de Hitos --}}
        <div class="bg-ui-card p-4 rounded-xl border border-ui-border shadow-xs flex flex-col justify-between">
            <div>
                <span class="text-[10px] font-black uppercase tracking-wider text-ui-muted block mb-1">Validación de Hitos</span>
                <div class="flex items-center justify-between">
                    <span class="text-2xl font-black font-mono text-ui-fg">
                        {{ $patrol->validated_checkpoints_count }} / {{ $patrol->total_checkpoints_count }}
                    </span>
                    <span class="text-base font-black font-mono {{ $patrol->validation_percentage === 100 ? 'text-emerald-500' : 'text-brand-cobalt dark:text-brand-cobalt-light' }}">
                        {{ $patrol->validation_percentage }}%
                    </span>
                </div>
            </div>
            <div class="w-full bg-slate-200 dark:bg-slate-700 h-2.5 rounded-full overflow-hidden mt-2">
                <div class="h-full rounded-full transition-all duration-300 {{ $patrol->validation_percentage === 100 ? 'bg-emerald-500' : 'bg-brand-cobalt' }}"
                     style="width: {{ $patrol->validation_percentage }}%"></div>
            </div>
        </div>
    </div>

    {{-- OBJETIVO / CONSIGNA --}}
    @if($patrol->mission_objective)
        <div class="bg-ui-card p-4 rounded-xl border border-ui-border text-xs">
            <span class="font-black text-ui-fg uppercase text-[11px] block mb-1">🎯 Objetivo / Misión del Patrullaje:</span>
            <p class="text-ui-muted italic">"{{ $patrol->mission_objective }}"</p>
        </div>
    @endif

    {{-- SECCIÓN CENTRAL: HITOS ASIGNADOS Y MAPA DE RUTA --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        {{-- LISTA DE HITOS Y VALIDACIÓN (Columna Izquierda: 7 columnas) --}}
        <div class="lg:col-span-7 space-y-4">
            <div class="bg-ui-card p-5 rounded-2xl border border-ui-border shadow-xs">
                <div class="flex items-center justify-between border-b border-ui-border pb-3 mb-4">
                    <div>
                        <h3 class="font-black text-base text-ui-fg flex items-center gap-2">
                            <span>📍</span> Puntos e Hitos de Control Asignados
                        </h3>
                        <p class="text-xs text-ui-muted">Validación por paso físico del móvil en terreno con captura de distancia GPS.</p>
                    </div>
                </div>

                <div class="space-y-3">
                    @forelse($patrol->checkpoints as $index => $cp)
                        <div class="p-3.5 rounded-xl border transition {{ $cp->status === 'Validado' ? 'bg-emerald-500/5 border-emerald-500/30' : ($cp->status === 'Omitido' ? 'bg-slate-100 dark:bg-slate-800/40 border-slate-300 opacity-60' : 'bg-ui-subtle border-ui-border') }}">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                
                                {{-- Información del Hito --}}
                                <div class="flex items-start gap-2.5">
                                    <div class="w-6 h-6 rounded-full flex items-center justify-center font-black text-xs shrink-0 mt-0.5 {{ $cp->status === 'Validado' ? 'bg-emerald-500 text-white' : ($cp->status === 'Omitido' ? 'bg-slate-400 text-white' : 'bg-brand-cobalt text-white') }}">
                                        {{ $cp->order }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-black text-sm text-ui-fg flex items-center gap-2">
                                            <span>{{ $cp->name }}</span>
                                            @if($cp->status === 'Validado')
                                                <span class="text-[10px] font-black uppercase text-emerald-600 bg-emerald-100 dark:bg-emerald-950 px-2 py-0.2 rounded-full border border-emerald-300">
                                                    ✓ Validado
                                                </span>
                                            @elseif($cp->status === 'Omitido')
                                                <span class="text-[10px] font-bold uppercase text-slate-500 bg-slate-200 dark:bg-slate-700 px-2 py-0.2 rounded-full">
                                                    Omitido
                                                </span>
                                            @else
                                                <span class="text-[10px] font-bold uppercase text-amber-600 bg-amber-100 dark:bg-amber-950 px-2 py-0.2 rounded-full border border-amber-300">
                                                    ⏳ Pendiente
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-ui-muted mt-0.5">{{ $cp->address ?: 'Ubicación comunal' }}</div>

                                        {{-- Datos de validación si ya se pasó --}}
                                        @if($cp->status === 'Validado')
                                            <div class="text-[11px] text-emerald-700 dark:text-emerald-300 mt-2 font-mono flex items-center gap-3 flex-wrap">
                                                <span>⏱ Paso: <strong>{{ $cp->validated_at?->format('H:i:s') }}</strong></span>
                                                @if(!is_null($cp->validation_distance_meters))
                                                    <span>📏 Distancia: <strong>{{ $cp->validation_distance_meters }} m</strong></span>
                                                @endif
                                                <span>👤 Validador: {{ $cp->validatedBy?->name ?? 'Inspector' }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Botones de Acción sobre el Hito --}}
                                @if($cp->status === 'Pendiente' && $patrol->status === 'En Curso')
                                    <div class="flex items-center gap-1.5 shrink-0" x-data>
                                        <button type="button"
                                                @click="
                                                    if (navigator.geolocation) {
                                                        navigator.geolocation.getCurrentPosition(
                                                            (pos) => $wire.validateCheckpoint({{ $cp->id }}, pos.coords.latitude, pos.coords.longitude),
                                                            () => $wire.validateCheckpoint({{ $cp->id }})
                                                        );
                                                    } else {
                                                        $wire.validateCheckpoint({{ $cp->id }});
                                                    }
                                                "
                                                class="bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold px-3 py-1.5 rounded-lg text-xs transition flex items-center gap-1 shadow-xs">
                                            <i class="fa-solid fa-check text-xs"></i>
                                            <span>Validar Paso</span>
                                        </button>
                                        
                                        <button type="button"
                                                wire:click="omitCheckpoint({{ $cp->id }})"
                                                wire:confirm="¿Desea omitir este punto de control?"
                                                class="text-ui-muted hover:text-red-500 p-1.5 text-xs rounded hover:bg-ui-subtle"
                                                title="Omitir hito">
                                            <i class="fa-solid fa-ban"></i>
                                        </button>
                                    </div>
                                @endif

                            </div>
                        </div>
                    @empty
                        <div class="text-center py-6 text-ui-muted text-xs">
                            No se asignaron hitos específicos a este patrullaje.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- PROCEDIMIENTOS INICIADOS DESDE ESTE PATRULLAJE --}}
            <div class="bg-ui-card p-5 rounded-2xl border border-ui-border shadow-xs">
                <div class="flex items-center justify-between border-b border-ui-border pb-3 mb-3">
                    <h3 class="font-black text-sm text-ui-fg flex items-center gap-2">
                        <span>🚨</span> Procedimientos Originados en este Patrullaje ({{ $patrol->procedures->count() }})
                    </h3>
                    <button type="button"
                            wire:click="startProcedure"
                            class="text-xs font-bold text-red-600 hover:text-red-800 hover:underline">
                        + Iniciar Nuevo Caso
                    </button>
                </div>

                @if($patrol->procedures->count() > 0)
                    <div class="divide-y divide-ui-border">
                        @foreach($patrol->procedures as $proc)
                            <div class="py-2.5 flex items-center justify-between gap-3 text-xs">
                                <div>
                                    <a href="/procedimientos/{{ $proc->id }}" target="_blank" class="font-mono font-black text-brand-cobalt dark:text-brand-cobalt-light hover:underline">
                                        #{{ $proc->id }} - {{ $proc->procedureType?->name ?? 'Procedimiento' }}
                                    </a>
                                    <div class="text-ui-muted text-[11px] truncate max-w-sm">{{ $proc->exact_address }}</div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-[11px] px-2 py-0.5 rounded font-bold {{ $proc->status === 'Cerrado' ? 'bg-slate-100 text-slate-700' : 'bg-red-100 text-red-700' }}">
                                        {{ $proc->status }}
                                    </span>
                                    <a href="/procedimientos/{{ $proc->id }}" target="_blank" class="text-ui-muted hover:text-brand-cobalt">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-xs text-ui-muted py-4 text-center">
                        No se han generado procedimientos durante este patrullaje preventivo.
                    </div>
                @endif
            </div>
        </div>

        {{-- MAPA TÁCTICO DE RUTA Y WAYPOINTS (Columna Derecha: 5 columnas) --}}
        <div class="lg:col-span-5 space-y-4">
            <div class="bg-ui-card p-4 rounded-2xl border border-ui-border shadow-xs">
                <div class="flex items-center justify-between mb-3 border-b border-ui-border pb-2">
                    <h3 class="font-black text-xs uppercase tracking-wider text-ui-fg flex items-center gap-1.5">
                        <span>🗺️</span> Ruta GPS y Puntos de Control
                    </h3>
                    <span class="text-[10px] font-mono text-ui-muted">
                        {{ count($patrol->gps_route ?: []) }} waypoints registrados
                    </span>
                </div>

                {{-- Canvas de Mapa --}}
                <div class="w-full h-[450px] rounded-xl overflow-hidden border border-ui-border relative" wire:ignore>
                    <div id="patrol-route-map" class="w-full h-full"></div>
                </div>

                {{-- Leyenda del Mapa --}}
                <div class="mt-3 pt-2 border-t border-ui-border grid grid-cols-2 gap-2 text-[11px] text-ui-muted font-bold">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        <span>Hito Validado</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-brand-cobalt"></span>
                        <span>Hito Pendiente</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-0.5 bg-blue-500"></span>
                        <span>Traza Ruta GPS</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs">🚓</span>
                        <span>Ubicación Móvil</span>
                    </div>
                </div>
            </div>

            {{-- Observaciones de Cierre si existen --}}
            @if($patrol->completion_notes)
                <div class="bg-ui-card p-4 rounded-xl border border-ui-border text-xs">
                    <span class="font-black text-ui-fg uppercase text-[10px] tracking-wider block mb-1">Observaciones de Término:</span>
                    <p class="text-ui-muted whitespace-pre-line">{{ $patrol->completion_notes }}</p>
                </div>
            @endif
        </div>

    </div>

    {{-- MODAL DE CIERRE / FINALIZACIÓN DE PATRULLAJE --}}
    @if($showCompleteModal)
        <div class="fixed inset-0 z-50 bg-slate-900/80 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-ui-card rounded-2xl shadow-2xl border border-ui-border w-full max-w-lg overflow-hidden">
                <div class="bg-ui-sidebar text-brand-white p-4 flex justify-between items-center">
                    <h3 class="font-black text-sm">Finalizar Patrullaje {{ $patrol->folio }}</h3>
                    <button type="button" wire:click="$set('showCompleteModal', false)" class="text-white/70 hover:text-white">✕</button>
                </div>

                <form wire:submit.prevent="completePatrol" class="p-5 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-ui-fg mb-1">Odómetro Final (Km)</label>
                        <input type="number"
                               wire:model="end_odometer"
                               placeholder="Ej: 145240"
                               class="w-full text-xs rounded-lg border-ui-border bg-ui-subtle text-ui-fg font-mono">
                        @error('end_odometer') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-ui-fg mb-1">Observaciones de Cierre / Novedades</label>
                        <textarea wire:model="completion_notes"
                                  rows="3"
                                  placeholder="Indique novedades de la ronda preventiva, estado de las vías, etc..."
                                  class="w-full text-xs rounded-lg border-ui-border bg-ui-subtle text-ui-fg"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 border-t border-ui-border pt-3">
                        <button type="button" wire:click="$set('showCompleteModal', false)" class="px-3 py-1.5 text-xs font-bold rounded-lg border border-ui-border text-ui-fg">Cancelar</button>
                        <button type="submit" class="px-4 py-1.5 text-xs font-black rounded-lg bg-brand-cobalt hover:bg-brand-cobalt-deep text-white">Confirmar Cierre de Patrullaje</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        const mapElement = document.getElementById("patrol-route-map");
        if (!mapElement || typeof google === 'undefined' || !google.maps) return;

        const defaultCenter = { lat: -33.7320, lng: -70.7420 };
        const map = new google.maps.Map(mapElement, {
            zoom: 13,
            center: defaultCenter,
            mapTypeId: 'roadmap',
            disableDefaultUI: false,
            zoomControl: true,
            mapTypeControl: false,
            streetViewControl: false,
        });

        const checkpoints = @json($patrol->checkpoints);
        const gpsRoute = @json($patrol->gps_route ?: []);
        const procedures = @json($patrol->procedures);
        const deviceLat = {{ $patrol->device?->last_lat ? (float)$patrol->device->last_lat : 'null' }};
        const deviceLng = {{ $patrol->device?->last_lng ? (float)$patrol->device->last_lng : 'null' }};

        const bounds = new google.maps.LatLngBounds();
        let hasPoints = false;

        // 1. Dibujar Traza de Ruta GPS si existe
        if (gpsRoute && gpsRoute.length > 1) {
            const pathCoords = gpsRoute.map(p => ({ lat: parseFloat(p.lat), lng: parseFloat(p.lng) }));
            const routePolyline = new google.maps.Polyline({
                path: pathCoords,
                geodesic: true,
                strokeColor: '#2563EB',
                strokeOpacity: 0.8,
                strokeWeight: 4,
                map: map,
            });
            pathCoords.forEach(c => bounds.extend(c));
            hasPoints = true;
        }

        // 2. Dibujar Checkpoints
        checkpoints.forEach(cp => {
            if (!cp.latitude || !cp.longitude) return;
            const pos = { lat: parseFloat(cp.latitude), lng: parseFloat(cp.longitude) };
            bounds.extend(pos);
            hasPoints = true;

            const isValidated = cp.status === 'Validado';
            const pinColor = isValidated ? '#10B981' : '#2C2E83';

            const marker = new google.maps.Marker({
                position: pos,
                map: map,
                label: {
                    text: String(cp.order),
                    color: '#FFFFFF',
                    fontWeight: 'bold',
                    fontSize: '11px',
                },
                title: `${cp.order}. ${cp.name} (${cp.status})`,
            });
        });

        // 3. Dibujar ubicación actual del móvil
        if (deviceLat && deviceLng) {
            const devPos = { lat: deviceLat, lng: deviceLng };
            bounds.extend(devPos);
            hasPoints = true;

            new google.maps.Marker({
                position: devPos,
                map: map,
                title: `🚓 ${@json($patrol->device?->name)}`,
            });
        }

        if (hasPoints) {
            map.fitBounds(bounds);
        }
    });
</script>
