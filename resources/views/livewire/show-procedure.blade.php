<div class="py-6 max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
    
    {{-- BARRA SUPERIOR DE COMANDO DEL PROCEDIMIENTO --}}
    <div class="bg-ui-sidebar text-brand-white rounded-xl shadow-md p-4 mb-6 flex flex-wrap justify-between items-center gap-4 border border-white/10">
        <div class="flex items-center gap-4">
            <a href="{{ route('dashboard') }}" class="bg-white/10 hover:bg-white/20 text-brand-white border border-white/20 px-3 py-1.5 rounded-lg text-xs font-bold transition inline-flex items-center gap-1.5">
                <i class="fa-solid fa-arrow-left text-[11px]" aria-hidden="true"></i>
                <span>Panel Central</span>
            </a>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-extrabold font-mono cad-tabular bg-brand-lime text-brand-black px-2.5 py-0.5 rounded">FOLIO #{{ $procedure->id }}</span>
                    <span class="text-xs font-mono cad-tabular font-bold text-brand-cobalt-light">{{ $procedure->created_at->format('d/m/Y H:i:s') }}</span>
                    <x-cad.procedure-status :status="$procedure->status" class="bg-white/15! text-white! border-white/25!" />
                    @if($procedure->derived_to)
                        <span class="inline-flex items-center gap-1 text-[11px] font-bold bg-indigo-500/25 text-indigo-100 border border-indigo-300/40 px-2 py-0.5 rounded">
                            <i class="fa-solid fa-share-from-square text-[10px]" aria-hidden="true"></i>
                            <span>{{ $procedure->derived_to }}</span>
                        </span>
                    @endif
                    @if($procedure->closure_resolution)
                        <span class="inline-flex items-center gap-1 text-[11px] font-bold bg-emerald-500/25 text-emerald-100 border border-emerald-300/40 px-2 py-0.5 rounded">
                            <i class="fa-solid fa-flag-checkered text-[10px]" aria-hidden="true"></i>
                            <span>{{ $procedure->closure_resolution }}</span>
                        </span>
                    @endif
                </div>
                <h1 class="text-xl font-extrabold mt-1 text-brand-white">
                    {{ $procedure->procedureType->name ?? 'Procedimiento' }} — <span class="text-brand-lime-light font-semibold">{{ $procedure->sector->name ?? '' }}</span>
                </h1>
            </div>
        </div>

        @php $sla = $procedure->sla_traffic_light; @endphp
        <div class="flex flex-wrap items-center gap-2.5">
            <x-cad.priority-badge :sla="$sla" class="bg-black/25! text-white! border-white/20!" />
            <a href="{{ route('procedures.print', $procedure->id) }}" target="_blank" class="bg-brand-lime hover:bg-brand-lime-mid text-brand-black text-xs font-extrabold py-2 px-3.5 rounded-lg shadow-xs inline-flex items-center gap-1.5 transition">
                <i class="fa-solid fa-print" aria-hidden="true"></i>
                <span>Ficha Oficial / PDF</span>
            </a>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-[#16835D]/15 border-l-4 border-[#16835D] text-ui-fg p-4 mb-6 rounded-lg shadow-xs font-bold text-sm flex items-center gap-2" role="status">
            <i class="fa-solid fa-circle-check text-[#16835D]" aria-hidden="true"></i>
            <span>{{ session('message') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        {{-- COLUMNA IZQUIERDA (4 cols): DETALLES, DESPACHO, GPS Y TRIPULACIÓN --}}
        <div class="lg:col-span-4 space-y-5">
            
            <div class="bg-white shadow-sm rounded-xl p-5 border border-slate-200 border-t-4 border-t-blue-600">
                <h3 class="text-sm font-black uppercase tracking-wider text-slate-700 border-b pb-2 mb-3">
                    📋 Ficha Base del Caso
                </h3>

                @if($procedure->parent_id)
                    <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-800 p-3.5 mb-4 rounded-lg flex justify-between items-center">
                        <div>
                            <h3 class="font-bold text-xs uppercase">⚠️ Procedimiento Anexo</h3>
                            <p class="text-xs">Vinculado al caso principal.</p>
                        </div>
                        <a href="{{ route('procedures.show', $procedure->parent_id) }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-1.5 px-3 rounded text-xs transition">
                            Caso #{{ $procedure->parent_id }} →
                        </a>
                    </div>
                @endif

                @if($procedure->preventive_patrol_id)
                    <div class="bg-emerald-50 dark:bg-emerald-950/40 border-l-4 border-emerald-500 text-emerald-800 dark:text-emerald-300 p-3.5 mb-4 rounded-lg flex justify-between items-center">
                        <div>
                            <h3 class="font-bold text-xs uppercase flex items-center gap-1.5">
                                <i class="fa-solid fa-shield-halved"></i>
                                <span>Originado en Patrullaje Preventivo</span>
                            </h3>
                            <p class="text-xs">Iniciado durante ronda preventiva de terreno.</p>
                        </div>
                        <a href="{{ route('patrols.preventive.show', $procedure->preventive_patrol_id) }}" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-1.5 px-3 rounded text-xs transition">
                            {{ $procedure->preventivePatrol?->folio ?? 'Patrullaje #'.$procedure->preventive_patrol_id }} →
                        </a>
                    </div>
                @endif
                            
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="font-bold text-slate-500">Categoría de Inicio (Llamado):</span>
                        <span class="font-semibold text-slate-800">{{ $procedure->initialProcedureType->name ?? $procedure->procedureType->name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-bold text-slate-500">Categoría Operativa Actual:</span>
                        <span class="font-black text-blue-700">
                            {{ $procedure->procedureType->name }}
                            @if($procedure->initial_procedure_type_id && $procedure->initial_procedure_type_id != $procedure->procedure_type_id)
                                <span class="text-[10px] bg-amber-100 text-amber-800 px-1.5 py-0.5 rounded ml-1">Recategorizado</span>
                            @endif
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-bold text-slate-500">Vía de Ingreso:</span>
                        <span class="text-slate-800">{{ $procedure->origin->name ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-bold text-slate-500">Hora Recepción:</span>
                        <span class="font-mono text-xs text-slate-800">{{ $procedure->created_at->format('d/m/Y H:i:s') }}</span>
                    </div>
                    @if($procedure->dispatched_at)
                        <div class="flex justify-between">
                            <span class="font-bold text-slate-500">Despacho Móvil:</span>
                            <span class="text-slate-800 font-semibold">{{ $procedure->dispatched_at->format('H:i:s') }} <span class="text-xs text-slate-500">({{ $procedure->dispatch_minutes }} min)</span></span>
                        </div>
                    @endif
                    @if($procedure->arrived_at)
                        <div class="flex justify-between">
                            <span class="font-bold text-slate-500">Llegada Confirmada:</span>
                            <span class="text-emerald-700 font-bold">{{ $procedure->arrived_at->format('H:i:s') }} ({{ $procedure->arrival_minutes }} min)</span>
                        </div>
                    @endif
                    <div class="pt-2 border-t border-slate-100">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-500 block text-xs uppercase">Ubicación:</span>
                            @if($procedure->latitude && $procedure->longitude)
                                <span class="text-[10px] font-mono font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 rounded">
                                    📍 {{ number_format((float) $procedure->latitude, 5) }}, {{ number_format((float) $procedure->longitude, 5) }}
                                </span>
                            @endif
                        </div>
                        <p class="font-bold text-slate-900 mt-0.5">{{ $procedure->sector->name }} — {{ $procedure->exact_address }}</p>
                        @if($procedure->address_reference)
                            <p class="text-xs text-slate-600 mt-0.5"><strong>Ref:</strong> {{ $procedure->address_reference }}</p>
                        @endif

                        @if($procedure->latitude && $procedure->longitude)
                            <div class="mt-2"
                                 wire:ignore
                                 x-data="{
                                     initProcMiniMap() {
                                         if (typeof google === 'undefined' || !google.maps) return;
                                         const pos = { lat: {{ (float) $procedure->latitude }}, lng: {{ (float) $procedure->longitude }} };
                                         const map = new google.maps.Map(this.$refs.procMapCanvas, {
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
                                             title: @js($procedure->exact_address),
                                         });
                                     }
                                 }"
                                 x-init="$nextTick(() => initProcMiniMap())">
                                <div x-ref="procMapCanvas" class="w-full h-40 rounded-lg border border-slate-200 shadow-inner"></div>
                            </div>
                        @endif
                    </div>
                    <div class="pt-2 border-t border-slate-100 flex justify-between items-center">
                        <span class="font-bold text-slate-500">Móvil Asignado:</span>
                        <span class="font-black {{ $procedure->device ? 'text-slate-900 bg-slate-100 px-2.5 py-0.5 rounded border' : 'text-red-600' }}">
                            {{ $procedure->device ? '🚓 ' . $procedure->device->name : 'Sin Asignar' }}
                        </span>
                    </div>
                    @if($procedure->derived_to)
                        <div class="pt-2 border-t border-slate-100">
                            <span class="font-bold text-slate-500 block text-xs uppercase">Derivado a Organismo Externo:</span>
                            <span class="inline-flex items-center gap-1.5 mt-1 text-xs font-black text-indigo-800 bg-indigo-50 border border-indigo-200 px-2.5 py-1 rounded-md">
                                <i class="fa-solid fa-share-from-square" aria-hidden="true"></i>
                                <span>{{ $procedure->derived_to }}</span>
                            </span>
                            @if($procedure->external_reference)
                                <p class="text-xs text-slate-600 mt-1"><strong>Ref. Externa:</strong> {{ $procedure->external_reference }}</p>
                            @endif
                        </div>
                    @endif
                    @if($procedure->closure_resolution)
                        <div class="pt-2 border-t border-slate-100">
                            <span class="font-bold text-slate-500 block text-xs uppercase">Resolución de Cierre:</span>
                            <span class="inline-flex items-center gap-1.5 mt-1 text-xs font-black text-emerald-800 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-md">
                                <i class="fa-solid fa-flag-checkered" aria-hidden="true"></i>
                                <span>{{ $procedure->closure_resolution }}</span>
                            </span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- PANEL DE CONFIRMACIÓN DE LLEGADA: MÓVIL (GPS) + OPERADORA (CENTRAL) --}}
            @if($procedure->device_id && !in_array($procedure->status, ['Cerrado', 'Cancelado']))
                <div class="bg-white shadow-sm rounded-xl p-5 border border-slate-200 border-t-4 {{ $procedure->arrived_at ? 'border-t-emerald-500' : ($procedure->mobile_arrived_at ? 'border-t-amber-500 bg-amber-50/40' : 'border-t-blue-600') }}"
                     x-data="{
                         gettingGps: false,
                         gpsError: '',
                         markGpsArrival() {
                             this.gettingGps = true;
                             this.gpsError = '';
                             if (!navigator.geolocation) {
                                 this.gettingGps = false;
                                 $wire.confirmMobileArrival(null, null);
                                 return;
                             }
                             navigator.geolocation.getCurrentPosition(
                                 (pos) => {
                                     this.gettingGps = false;
                                     $wire.confirmMobileArrival(pos.coords.latitude, pos.coords.longitude);
                                 },
                                 (err) => {
                                     this.gettingGps = false;
                                     this.gpsError = 'No se pudo obtener GPS preciso; registrando llegada sin coordenadas.';
                                     $wire.confirmMobileArrival(null, null);
                                 },
                                 { enableHighAccuracy: true, timeout: 8000 }
                             );
                         }
                     }">
                    <h3 class="text-sm font-black uppercase tracking-wider text-slate-800 border-b pb-2 mb-3 flex items-center justify-between">
                        <span>📍 Control de Llegada (2 Pasos)</span>
                        @if($procedure->arrived_at)
                            <span class="bg-emerald-100 text-emerald-800 text-xs px-2 py-0.5 rounded-full font-bold">✓ Confirmado</span>
                        @elseif($procedure->mobile_arrived_at)
                            <span class="bg-amber-200 text-amber-900 text-xs px-2 py-0.5 rounded-full font-bold animate-pulse">Pendiente Central</span>
                        @endif
                    </h3>

                    {{-- Paso 1: Marca del Móvil (GPS) --}}
                    <div class="mb-3 p-3 rounded-lg border {{ $procedure->mobile_arrived_at ? 'bg-emerald-50 border-emerald-200' : 'bg-slate-50 border-slate-200' }}">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-xs font-bold uppercase text-slate-600">1. Reporte del Móvil (Terreno)</span>
                            @if($procedure->mobile_arrived_at)
                                <span class="text-xs font-bold text-emerald-700">{{ $procedure->mobile_arrived_at->format('H:i:s') }}</span>
                            @endif
                        </div>

                        @if($procedure->mobile_arrived_at)
                            <p class="text-xs text-slate-700">
                                Móvil marcó llegada en terreno.
                                @if(!is_null($procedure->mobile_arrival_distance))
                                    <strong class="{{ $procedure->mobile_arrival_distance <= 150 ? 'text-emerald-700' : 'text-amber-700' }}">
                                        Distancia al punto: {{ $procedure->mobile_arrival_distance }} m.
                                    </strong>
                                @endif
                            </p>
                            @if($procedure->mobile_arrival_lat && $procedure->mobile_arrival_lng)
                                <a href="https://www.google.com/maps?q={{ $procedure->mobile_arrival_lat }},{{ $procedure->mobile_arrival_lng }}" target="_blank" class="text-[11px] text-blue-600 hover:underline font-semibold inline-block mt-1">
                                    🗺️ Ver ubicación GPS del móvil ({{ $procedure->mobile_arrival_lat }}, {{ $procedure->mobile_arrival_lng }})
                                </a>
                            @endif
                        @else
                            <p class="text-xs text-slate-500 mb-2">El inspector/móvil presiona al llegar para georreferenciar su posición.</p>
                            <button type="button" @click="markGpsArrival()" :disabled="gettingGps" class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-xs font-bold py-2 px-3 rounded-lg shadow flex items-center justify-center gap-2 transition">
                                <span x-show="!gettingGps">📡 Marcar Llegada del Móvil (Con GPS)</span>
                                <span x-show="gettingGps">⏳ Obteniendo coordenadas GPS...</span>
                            </button>
                            <p x-show="gpsError" x-text="gpsError" class="text-[11px] text-amber-600 mt-1"></p>
                        @endif
                    </div>

                    {{-- Paso 2: Confirmación de Operadora en Central --}}
                    @hasanyrole('Administrador|Supervisor|Operador')
                    <div class="p-3 rounded-lg border {{ $procedure->arrived_at ? 'bg-emerald-50 border-emerald-200' : 'bg-blue-50 border-blue-200' }}">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-xs font-bold uppercase text-slate-600">2. Validación Operadora (Central)</span>
                            @if($procedure->arrived_at)
                                <span class="text-xs font-bold text-emerald-700">{{ $procedure->arrived_at->format('H:i:s') }}</span>
                            @endif
                        </div>

                        @if($procedure->arrived_at)
                            <p class="text-xs text-emerald-800 font-semibold">
                                ✓ Llegada validada por Central ({{ $procedure->arrival_minutes }} min desde despacho).
                            </p>
                        @else
                            <p class="text-xs text-blue-800 mb-2">
                                @if($procedure->mobile_arrived_at)
                                    ⚠️ El móvil ya reportó llegada por GPS. Confirma para pasar a <strong>En el Lugar</strong>.
                                @else
                                    Confirma la llegada cuando el móvil lo indique por radio o GPS.
                                @endif
                            </p>
                            <button type="button" wire:click="confirmOperatorArrival" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold py-2 px-3 rounded-lg shadow transition">
                                ✅ Confirmar Llegada al Lugar (Operadora)
                            </button>
                        @endif
                    </div>
                    @endhasanyrole
                </div>
            @endif

            @if(!$procedure->device_id)
            <div class="bg-amber-50 shadow-sm rounded-xl p-5 border-2 border-amber-300">
                <h3 class="text-sm font-black uppercase text-amber-900 mb-1 flex items-center gap-1.5">
                    🚨 Caso sin Móvil Asignado
                </h3>
                <p class="text-xs text-amber-800 mb-3">Seleccione un móvil disponible para despachar a terreno:</p>
                
                @php
                    $dispatchDeviceOptions = $devices->map(fn($d) => [
                        'value' => $d->id,
                        'label' => $d->name,
                        'meta'  => $d->effective_status . ($d->active_procedures_count > 0 ? ' · ' . $d->active_procedures_count . ' caso(s)' : ''),
                    ])->values()->toArray();
                @endphp
                <div class="flex flex-col sm:flex-row gap-2">
                    <div class="flex-1">
                        <x-searchable-select
                            wire:model="new_device_id"
                            :options="$dispatchDeviceOptions"
                            placeholder="Seleccionar móvil para despachar..."
                            searchPlaceholder="Buscar móvil (ej: Móvil 15, Moto, Patrulla)..."
                        />
                    </div>
                    <button wire:click="assignDevice" class="bg-amber-600 hover:bg-amber-700 text-white font-black py-2 px-4 rounded-lg shadow text-xs transition shrink-0">
                        Despachar
                    </button>
                </div>
                @error('new_device_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>
            @endif

            @if($procedure->caller_name || $procedure->caller_phone)
            <div class="bg-white shadow-sm rounded-xl p-5 border border-slate-200 border-t-4 border-t-slate-500">
                <h3 class="text-sm font-black uppercase tracking-wider text-slate-700 border-b pb-2 mb-3">👤 Datos del Informante</h3>
                <div class="space-y-2 text-sm">
                    <div><span class="font-bold text-slate-500">Nombre:</span> {{ $procedure->caller_name ?: 'Anónimo' }}</div>
                    <div><span class="font-bold text-slate-500">Teléfono:</span> <span class="font-semibold">{{ $procedure->caller_phone ?: 'N/A' }}</span></div>
                    <div><span class="font-bold text-slate-500">RUT/Doc:</span> {{ $procedure->caller_id_document ?: 'N/A' }}</div>
                </div>

                @if(isset($previousCallerCases) && $previousCallerCases->count() > 0)
                    <div class="mt-4 pt-3 border-t border-slate-200">
                        <p class="text-xs font-bold text-slate-500 uppercase mb-2">📋 Otros Casos de este Teléfono ({{ $previousCallerCases->count() }}):</p>
                        <div class="space-y-1.5">
                            @foreach($previousCallerCases as $prevCase)
                                <div class="flex justify-between items-center text-xs bg-slate-50 p-2 rounded border">
                                    <a href="{{ route('procedures.show', $prevCase->id) }}" class="font-bold text-blue-600 hover:underline">
                                        #{{ $prevCase->id }} - {{ $prevCase->procedureType->name ?? 'Caso' }}
                                    </a>
                                    <span class="text-slate-500">{{ $prevCase->created_at->format('d/m/Y') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
            @endif

            <div class="bg-white shadow-sm rounded-xl p-5 border border-slate-200 border-t-4 border-t-indigo-600">
                <h3 class="text-sm font-black uppercase tracking-wider text-slate-700 border-b pb-2 mb-3">👮 Tripulación a Cargo</h3>

                @if(!empty($procedure->metadata['_assigned_inspector_name']))
                    <div class="mb-3 p-2.5 bg-emerald-50 border border-emerald-200 rounded-lg flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-black uppercase text-emerald-700 block">Inspector Asignado / Traspasado</span>
                            <span class="text-xs font-extrabold text-emerald-950">{{ $procedure->metadata['_assigned_inspector_name'] }}</span>
                        </div>
                        <i class="fa-solid fa-user-shield text-emerald-600"></i>
                    </div>
                @endif

                @if($procedure->patrolAssignment && $procedure->patrolAssignment->crew->count() > 0)
                    <div class="space-y-2">
                        @foreach($procedure->patrolAssignment->crew as $inspector)
                            <div class="flex items-center p-2 bg-indigo-50/60 rounded-lg border border-indigo-100">
                                <div class="bg-indigo-600 text-white rounded-full w-7 h-7 flex items-center justify-center font-bold text-xs mr-3">
                                    {{ substr($inspector->name, 0, 1) }}
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-800">{{ $inspector->name }}</p>
                                    <p class="text-xs text-indigo-600 font-semibold">{{ $inspector->pivot->role }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center p-3 bg-slate-50 border border-dashed rounded-lg text-slate-500 text-xs italic">
                        Sin tripulación registrada para este móvil en el turno.
                    </div>
                @endif

                {{-- Opción de traspasar procedimiento a otro inspector (Administrador, Supervisor y Operador) --}}
                @hasanyrole('Administrador|Supervisor|Operador')
                    @if(!in_array($procedure->status, ['Cerrado', 'Cancelado']))
                        <form wire:submit.prevent="transferProcedure" class="mt-4 pt-3 border-t border-slate-200 space-y-2.5">
                            <label class="block text-xs font-black uppercase text-slate-700">
                                🔄 Traspasar Procedimiento a Otro Inspector
                            </label>
                            <select wire:model="transfer_inspector_id" class="w-full rounded-lg border-slate-300 text-xs font-semibold">
                                <option value="">-- Seleccione inspector destino --</option>
                                @foreach($inspectors ?? [] as $insp)
                                    <option value="{{ $insp['id'] }}">
                                        {{ $insp['name'] }}{{ !empty($insp['unit']) ? ' (' . $insp['unit'] . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('transfer_inspector_id') <span class="text-red-500 text-xs block">{{ $message }}</span> @enderror

                            <input type="text"
                                   wire:model="transfer_notes"
                                   placeholder="Motivo o instrucción del traspaso (opcional)..."
                                   class="w-full rounded-lg border-slate-300 text-xs">

                            <button type="submit"
                                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-3 rounded-lg text-xs shadow-xs transition flex items-center justify-center gap-1.5">
                                <i class="fa-solid fa-people-arrows"></i>
                                <span>Traspasar Procedimiento</span>
                            </button>
                        </form>
                    @endif
                @endhasanyrole
            </div>

            @if(!$procedure->parent_id && !in_array($procedure->status, ['Cerrado', 'Cancelado']) && $procedure->children->count() === 0)
            <div class="bg-slate-50 shadow-sm rounded-xl p-5 border border-slate-200">
                <h3 class="text-sm font-black uppercase text-slate-700 mb-1">🔗 Agrupar / Vincular Caso Duplicado</h3>
                <p class="text-xs text-slate-500 mb-3">Si este folio corresponde al mismo incidente de otro caso principal, búscalo y anéxalo con vista previa:</p>

                @if($procedure->device)
                    <div class="mb-3 p-2.5 rounded-lg bg-amber-50 border border-amber-200 text-[11px] font-semibold text-amber-800">
                        ⚠️ Este caso tiene el móvil <strong>{{ $procedure->device->name }}</strong> asignado. Al anexarlo se cerrará y liberará la unidad.
                    </div>
                @endif

                <button type="button"
                        wire:click="openParentLinkModal"
                        class="w-full bg-slate-800 hover:bg-slate-900 dark:bg-brand-cobalt-mid dark:hover:bg-brand-cobalt-light dark:text-brand-black text-white font-bold py-2.5 px-4 rounded-lg text-xs shadow-sm transition inline-flex items-center justify-center gap-2">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <span>Buscar Caso Principal para Vincular...</span>
                </button>
                @error('parent_id_to_link') <span class="text-red-500 text-xs mt-1.5 block font-semibold">{{ $message }}</span> @enderror
            </div>
            @endif

            @if($procedure->evidences->count() > 0)
            @php
                $lightboxItems = $procedure->evidences->values()->map(fn ($ev) => [
                    'url' => asset('storage/' . $ev->file_path),
                    'date' => $ev->created_at->format('d/m/Y H:i'),
                    'user' => $ev->user->name ?? 'Sistema',
                ])->toArray();
            @endphp
            <div class="bg-white shadow-sm rounded-xl p-5 border border-slate-200 border-t-4 border-t-indigo-500"
                 x-data="{
                     open: false,
                     currentIndex: 0,
                     items: @js($lightboxItems),
                     show(idx) { this.currentIndex = idx; this.open = true; },
                     close() { this.open = false; },
                     prev() { this.currentIndex = (this.currentIndex - 1 + this.items.length) % this.items.length; },
                     next() { this.currentIndex = (this.currentIndex + 1) % this.items.length; }
                 }"
                 @keydown.escape.window="if (open) close()"
                 @keydown.arrow-left.window="if (open && items.length > 1) prev()"
                 @keydown.arrow-right.window="if (open && items.length > 1) next()">
                <div class="flex items-center justify-between border-b pb-2 mb-3">
                    <h3 class="text-sm font-black uppercase text-slate-700">📷 Evidencia Fotográfica ({{ $procedure->evidences->count() }})</h3>
                    <span class="text-[10px] font-semibold text-slate-400">Clic para ampliar</span>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    @foreach($procedure->evidences->values() as $idx => $evidence)
                        <button type="button"
                                @click="show({{ $idx }})"
                                class="block group relative text-left rounded-lg overflow-hidden focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <img src="{{ asset('storage/' . $evidence->file_path) }}"
                                 alt="Evidencia #{{ $idx + 1 }}"
                                 class="rounded-lg shadow group-hover:scale-105 transition-transform duration-200 h-24 w-full object-cover">
                            <span class="absolute inset-0 bg-black/0 group-hover:bg-black/25 transition flex items-center justify-center">
                                <i class="fa-solid fa-magnifying-glass-plus text-white opacity-0 group-hover:opacity-100 transition text-lg drop-shadow" aria-hidden="true"></i>
                            </span>
                            <span class="absolute bottom-0 left-0 bg-black/65 text-white text-[10px] px-1.5 py-0.5 rounded-tr">
                                {{ $evidence->created_at->format('d/m H:i') }}
                            </span>
                        </button>
                    @endforeach
                </div>

                {{-- LIGHTBOX MODAL --}}
                <template x-teleport="body">
                    <div x-show="open"
                         x-transition.opacity
                         style="display: none;"
                         role="dialog"
                         aria-modal="true"
                         aria-label="Visor de evidencia fotográfica"
                         class="fixed inset-0 z-[100] bg-black/90 backdrop-blur-xs flex flex-col items-center justify-center p-4"
                         @click.self="close()">
                        <div class="w-full max-w-5xl flex items-center justify-between text-white mb-3 px-2">
                            <div class="text-xs sm:text-sm font-bold flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded bg-white/15 font-mono" x-text="(currentIndex + 1) + ' / ' + items.length"></span>
                                <span x-text="items[currentIndex]?.date"></span>
                                <span class="text-white/60">·</span>
                                <span class="text-white/80" x-text="items[currentIndex]?.user"></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <a :href="items[currentIndex]?.url"
                                   target="_blank"
                                   class="px-3 py-1.5 rounded-lg bg-white/15 hover:bg-white/25 text-white text-xs font-bold inline-flex items-center gap-1.5 transition">
                                    <i class="fa-solid fa-up-right-from-square text-[11px]" aria-hidden="true"></i>
                                    <span>Abrir original</span>
                                </a>
                                <button type="button"
                                        @click="close()"
                                        aria-label="Cerrar visor"
                                        class="w-9 h-9 rounded-lg bg-white/15 hover:bg-white/25 text-white inline-flex items-center justify-center transition">
                                    <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>

                        <div class="relative w-full max-w-5xl flex items-center justify-center">
                            <button type="button"
                                    x-show="items.length > 1"
                                    @click="prev()"
                                    aria-label="Imagen anterior"
                                    class="absolute left-2 sm:left-4 z-10 w-10 h-10 rounded-full bg-black/60 hover:bg-black/85 border border-white/20 text-white inline-flex items-center justify-center transition">
                                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                            </button>

                            <img :src="items[currentIndex]?.url"
                                 alt="Evidencia ampliada"
                                 class="max-h-[78vh] max-w-full rounded-xl shadow-2xl object-contain border border-white/10">

                            <button type="button"
                                    x-show="items.length > 1"
                                    @click="next()"
                                    aria-label="Imagen siguiente"
                                    class="absolute right-2 sm:right-4 z-10 w-10 h-10 rounded-full bg-black/60 hover:bg-black/85 border border-white/20 text-white inline-flex items-center justify-center transition">
                                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
            @endif

            @if($procedure->children->count() > 0)
            <div class="bg-white shadow-sm rounded-xl p-5 border border-slate-200 border-t-4 border-t-emerald-500">
                <h3 class="text-sm font-black uppercase text-slate-700 border-b pb-2 mb-3 flex justify-between items-center">
                    <span>Casos Anexos (Duplicados)</span>
                    <span class="bg-emerald-100 text-emerald-800 text-xs px-2 py-0.5 rounded-full">{{ $procedure->children->count() }}</span>
                </h3>
                <div class="space-y-2">
                    @foreach($procedure->children as $child)
                        <div class="bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-xs space-y-1">
                            <div class="flex justify-between items-center gap-2">
                                <a href="{{ route('procedures.show', $child->id) }}" target="_blank" class="font-bold text-blue-600 hover:underline">Caso #{{ $child->id }}</a>
                                <div class="flex items-center gap-2">
                                    <span class="text-slate-500 font-mono text-[11px]">{{ $child->created_at->format('H:i') }}</span>
                                    @if($canManualOverrideStatus)
                                        <button type="button"
                                                wire:click="unlinkChildProcedure({{ $child->id }})"
                                                wire:confirm="¿Desvincular y reabrir el Caso Anexo #{{ $child->id }}?"
                                                class="text-[10px] font-bold text-red-600 hover:underline">
                                            Desvincular
                                        </button>
                                    @endif
                                </div>
                            </div>
                            <p class="text-slate-700"><strong>Llama:</strong> {{ $child->caller_name ?: 'Anónimo' }} ({{ $child->caller_phone ?: 'S/T' }})</p>
                            @if($child->evidences->count() > 0 || $child->calls->count() > 0)
                                <p class="text-[10px] text-slate-500">
                                    @if($child->calls->count() > 0)
                                        <span class="mr-2">📞 {{ $child->calls->count() }} llamado(s)</span>
                                    @endif
                                    @if($child->evidences->count() > 0)
                                        <span>📷 {{ $child->evidences->count() }} evidencia(s)</span>
                                    @endif
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Grabación Telefónica y Llamadas Vinculadas (Oculta para el rol Inspector) --}}
            @unless(auth()->user()?->isInspectorOnly())
                @php
                    $callsWithRecordings = $linkedCalls->filter(fn ($c) => ! empty($c->recording_path));
                @endphp
                @if($linkedCalls->count() > 0)
                    <div class="p-4 bg-slate-900 rounded-xl shadow-inner space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="text-xs text-emerald-400 font-bold uppercase block">
                                🎙️ Grabación Telefónica @if($linkedCalls->count() > 1) ({{ $linkedCalls->count() }} llamados) @endif
                            </label>
                            @if($callsWithRecordings->count() === 0)
                                <span class="text-[10px] text-slate-400">Sin archivo de audio</span>
                            @endif
                        </div>

                        <div class="space-y-2.5">
                            @foreach($linkedCalls as $linkedCall)
                                <div wire:key="proc-linked-call-{{ $linkedCall->id }}" class="p-2.5 rounded-lg bg-white/5 border border-white/10 space-y-1.5">
                                    <div class="flex flex-wrap items-center justify-between gap-1 text-[11px]">
                                        <span class="font-mono font-bold text-white">{{ $linkedCall->caller_id ?: 'Desconocido' }}</span>
                                        <div class="flex items-center gap-1.5">
                                            @if($linkedCall->procedure_id && (int) $linkedCall->procedure_id !== (int) $procedure->id)
                                                <span class="px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-300 text-[10px] font-bold">
                                                    Anexo #{{ $linkedCall->procedure_id }}
                                                </span>
                                            @endif
                                            <span class="font-mono text-slate-300">{{ $linkedCall->created_at?->format('d/m H:i') }}</span>
                                        </div>
                                    </div>
                                    @if($linkedCall->recording_path)
                                        <audio wire:key="proc-call-audio-{{ $linkedCall->id }}" controls class="w-full h-8" preload="none" src="{{ route('calls.play', $linkedCall->id) }}">
                                            <source src="{{ route('calls.play', $linkedCall->id) }}" type="audio/wav">
                                            Tu navegador no soporta audio.
                                        </audio>
                                    @else
                                        <span class="text-[10px] text-slate-400 italic block">Llamada vinculada sin grabación disponible.</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endunless

        </div>

        {{-- COLUMNA DERECHA (8 cols): CATEGORIZACIÓN OPERATIVA + METADATOS DINÁMICOS + BITÁCORA --}}
        <div class="lg:col-span-8 space-y-6">
            
            {{-- PANEL DE CATEGORIZACIÓN OPERATIVA Y ANTECEDENTES / METADATOS DINÁMICOS --}}
            <div class="bg-white shadow-sm rounded-xl p-6 border border-slate-200 border-t-4 border-t-indigo-600">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 border-b border-slate-200 pb-3 mb-4">
                    <div>
                        <h3 class="text-base font-black text-slate-800 flex items-center gap-2">
                            🧩 Categorización Operativa y Antecedentes (Metadatos)
                        </h3>
                        <p class="text-xs text-slate-500">
                            Confirma o recategoriza el procedimiento según lo constatado en terreno y registra los datos específicos (ej: vehículos, PPU, lesionados, especies).
                        </p>
                    </div>
                    @if($procedure->initialProcedureType)
                        <div class="text-right bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-200">
                            <span class="text-[10px] font-bold uppercase text-slate-500 block">Tipificación Inicial (Llamado)</span>
                            <span class="text-xs font-black text-slate-800">{{ $procedure->initialProcedureType->name }}</span>
                        </div>
                    @endif
                </div>

                <form wire:submit.prevent="saveMetadataAndCategory" class="space-y-4">
                    {{-- Selector de Recategorización Operativa --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-slate-50 p-3.5 rounded-lg border border-slate-200">
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-700 mb-1">
                                Categorización Operativa / Confirmada en Terreno
                            </label>
                            <x-searchable-select
                                wire:model.live="operational_procedure_type_id"
                                :options="$procedureTypes"
                                :allowClear="false"
                                :disabled="$procedure->status === 'Cerrado'"
                                placeholder="Seleccione o digite procedimiento..."
                                searchPlaceholder="Digite parte del procedimiento para filtrar..."
                            />
                        </div>
                        <div class="flex items-end">
                            <p class="text-xs text-slate-500 leading-relaxed">
                                💡 Si al llegar al lugar el procedimiento corresponde a otra tipificación (ej: de <em>Ruidos Molestos</em> pasa a <em>Violencia Intrafamiliar</em>), cámbiala aquí; los campos meta se adaptarán automáticamente.
                            </p>
                        </div>
                    </div>

                    {{-- Campos Meta Dinámicos de la Categoría --}}
                    @if(count($currentMetaFields) > 0)
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3.5">
                            @foreach($currentMetaFields as $field)
                                @php
                                    $isFieldEditable = ($field['editable'] ?? true) && $procedure->status !== 'Cerrado';
                                    $isLockedByOther = ($field['editable'] ?? true) === false;
                                @endphp
                                <div class="p-3 rounded-lg border {{ $isLockedByOther ? 'bg-slate-100 border-slate-200' : 'bg-indigo-50/40 border-indigo-100' }}">
                                    <div class="flex items-center justify-between gap-1 mb-1">
                                        <label class="block text-xs font-bold text-indigo-950">
                                            {{ $field['label'] }}
                                        </label>
                                        @if($isLockedByOther)
                                            <span class="text-[10px] font-semibold text-slate-500" title="Registrado por otro usuario; no editable">
                                                🔒 Solo lectura
                                            </span>
                                        @endif
                                    </div>
                                    @if(($field['type'] ?? 'text') === 'select')
                                        <select wire:model="metaValues.{{ $field['key'] }}" class="w-full rounded-lg border-indigo-200 text-sm font-semibold {{ $isFieldEditable ? 'bg-white' : 'bg-slate-100 text-slate-500 cursor-not-allowed' }}" @disabled(!$isFieldEditable)>
                                            <option value="">Sin especificar...</option>
                                            @foreach(array_filter(array_map('trim', explode(',', $field['options'] ?? ''))) as $opt)
                                                <option value="{{ $opt }}">{{ $opt }}</option>
                                            @endforeach
                                        </select>
                                    @elseif(($field['type'] ?? 'text') === 'boolean')
                                        <select wire:model="metaValues.{{ $field['key'] }}" class="w-full rounded-lg border-indigo-200 text-sm font-semibold {{ $isFieldEditable ? 'bg-white' : 'bg-slate-100 text-slate-500 cursor-not-allowed' }}" @disabled(!$isFieldEditable)>
                                            <option value="">Sin especificar</option>
                                            <option value="Sí">Sí</option>
                                            <option value="No">No</option>
                                        </select>
                                    @elseif(($field['type'] ?? 'text') === 'number')
                                        <input type="number" min="0" wire:model="metaValues.{{ $field['key'] }}" placeholder="{{ $field['placeholder'] ?? '0' }}" class="w-full rounded-lg border-indigo-200 text-sm font-semibold {{ $isFieldEditable ? 'bg-white' : 'bg-slate-100 text-slate-500 cursor-not-allowed' }}" @disabled(!$isFieldEditable)>
                                    @else
                                        <input type="text" wire:model="metaValues.{{ $field['key'] }}" placeholder="{{ $field['placeholder'] ?? 'Ingrese antecedente...' }}" class="w-full rounded-lg border-indigo-200 text-sm font-semibold {{ $isFieldEditable ? 'bg-white' : 'bg-slate-100 text-slate-500 cursor-not-allowed' }}" @disabled(!$isFieldEditable)>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-xs text-slate-500 bg-slate-50 p-3 rounded-lg border border-dashed text-center">
                            Esta categoría no tiene campos predefinidos, pero puedes añadir antecedentes específicos abajo o configurarlos en Mantenedores.
                        </div>
                    @endif

                    @if($procedure->status !== 'Cerrado')
                        {{-- Agregar antecedente / metadato adicional libre --}}
                        <div class="pt-3 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-stretch sm:items-end gap-3">
                            <div class="flex-1 grid grid-cols-1 sm:grid-cols-5 gap-2 bg-slate-50 p-2.5 rounded-lg border border-slate-200">
                                <div class="sm:col-span-2">
                                    <label class="block text-[10px] font-bold uppercase text-slate-500">Añadir Otro Antecedente (Etiqueta)</label>
                                    <input type="text" wire:model="customMetaLabel" placeholder="Ej: PPU Vehículo 3 / N° Parte" class="mt-0.5 w-full rounded border-slate-300 text-xs">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-[10px] font-bold uppercase text-slate-500">Valor</label>
                                    <input type="text" wire:model="customMetaValue" placeholder="Ej: ZZZZ-99" class="mt-0.5 w-full rounded border-slate-300 text-xs">
                                </div>
                                <div class="sm:col-span-1 flex items-end">
                                    <button type="button" wire:click="addCustomMetadataField" class="w-full bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold py-1.5 px-2 rounded text-xs transition">
                                        + Añadir
                                    </button>
                                </div>
                            </div>

                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-black py-2.5 px-5 rounded-lg shadow text-xs whitespace-nowrap transition">
                                💾 Guardar Categoría y Antecedentes
                            </button>
                        </div>
                    @endif
                </form>
            </div>

            {{-- FORMULARIO PARA AGREGAR NOVEDAD A LA BITÁCORA --}}
            @if($procedure->status !== 'Cerrado')
            @php
                $isDerivation = $this->isDerivationAction();
                $isClosure = $this->isClosureAction();
                $isArrival = $this->isArrivalAction();
                $willChangeStatus = $resolvedNextStatus !== $procedure->status;
                $submitLabel = match (true) {
                    $isClosure => 'Cerrar Procedimiento',
                    $isDerivation && $close_after_derivation => 'Registrar Derivación y Cerrar',
                    $isDerivation => 'Registrar Derivación',
                    $isArrival => 'Registrar Llegada al Lugar',
                    default => 'Guardar en Bitácora',
                };
                $submitBtnClass = match (true) {
                    $isClosure || ($isDerivation && $close_after_derivation) => 'bg-emerald-600 hover:bg-emerald-700',
                    $isDerivation => 'bg-indigo-600 hover:bg-indigo-700',
                    default => 'bg-blue-600 hover:bg-blue-700',
                };
                $descriptionPlaceholder = match (true) {
                    $isDerivation => 'Detalles adicionales de la coordinación o entrega al organismo externo (opcional)...',
                    $isClosure => 'Observaciones finales del cierre del procedimiento (opcional)...',
                    default => 'Escriba los detalles reportados por la unidad o central...',
                };
            @endphp
            <div class="bg-white shadow-sm rounded-xl p-6 border border-slate-200">
                <h3 class="text-base font-black text-slate-800 mb-4">✍️ Registrar Actuación / Novedad en Bitácora</h3>
                <form wire:submit.prevent="saveLog" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                        {{-- 1. Tipo de Actuación (guía todo el formulario) --}}
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Tipo de Actuación / Etapa</label>
                            <select wire:model.live="action_type" class="w-full rounded-lg border-slate-300 shadow-sm text-sm font-semibold">
                                @foreach($logActions as $actOption)
                                    <option value="{{ $actOption }}">{{ $actOption }}</option>
                                @endforeach
                            </select>
                            @error('action_type') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        {{-- 2. Estado Operativo Resultante (Automático según la actuación) --}}
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-bold uppercase text-slate-600">Estado Operativo Resultante</label>
                                @if($canManualOverrideStatus)
                                    <label class="inline-flex items-center gap-1.5 text-[11px] font-bold text-slate-500 hover:text-slate-700 cursor-pointer">
                                        <input type="checkbox" wire:model.live="manual_status_override" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                        <span>Ajuste manual</span>
                                    </label>
                                @endif
                            </div>

                            @if($manual_status_override && $canManualOverrideStatus)
                                <select wire:model.live="status" class="w-full rounded-lg border-indigo-400 bg-indigo-50/40 shadow-sm text-sm font-black text-indigo-800">
                                    <option value="Pendiente">Pendiente</option>
                                    <option value="En Camino">En Camino</option>
                                    <option value="En el Lugar">En el Lugar</option>
                                    <option value="Derivado">Derivado</option>
                                    <option value="Cerrado">Cerrado</option>
                                </select>
                            @else
                                <div class="min-h-[40px] px-3 py-2 rounded-lg border flex flex-wrap items-center justify-between gap-2 text-xs {{ $willChangeStatus ? 'bg-blue-50/70 border-blue-200 text-blue-900' : 'bg-slate-50 border-slate-200 text-slate-700' }}">
                                    @if($willChangeStatus)
                                        <div class="flex items-center gap-2 font-extrabold">
                                            <span class="px-2 py-0.5 rounded bg-white border border-slate-200 text-slate-600">{{ $procedure->status }}</span>
                                            <i class="fa-solid fa-arrow-right text-blue-600 text-[10px]" aria-hidden="true"></i>
                                            <span class="px-2 py-0.5 rounded {{ $resolvedNextStatus === 'Cerrado' ? 'bg-emerald-600 text-white' : ($resolvedNextStatus === 'Derivado' ? 'bg-indigo-600 text-white' : 'bg-blue-600 text-white') }}">
                                                {{ $resolvedNextStatus }}
                                            </span>
                                        </div>
                                        <span class="text-[11px] font-semibold text-blue-700">
                                            {{ $resolvedNextStatus === 'Cerrado' ? 'Finaliza el caso y libera móvil' : 'Cambio automático por actuación' }}
                                        </span>
                                    @else
                                        <div class="flex items-center gap-2 font-bold">
                                            <span class="px-2 py-0.5 rounded bg-white border border-slate-200 text-slate-700">{{ $procedure->status }}</span>
                                            <span class="text-slate-500 font-medium">Se mantiene en la etapa actual</span>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- BLOQUE CONTEXTUAL: DERIVACIÓN / COORDINACIÓN EXTERNA --}}
                    @if($isDerivation)
                        <div class="p-4 rounded-xl bg-indigo-50/70 border border-indigo-200 space-y-3">
                            <div class="flex items-center gap-2 text-xs font-black uppercase text-indigo-800">
                                <i class="fa-solid fa-share-from-square" aria-hidden="true"></i>
                                <span>Detalle de Derivación a Organismo Externo</span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-indigo-900 mb-1">Organismo / Externo al que se deriva *</label>
                                    <select wire:model.live="external_agency" class="w-full rounded-lg border-indigo-300 bg-white shadow-sm text-sm font-semibold text-slate-800">
                                        <option value="">-- Seleccione organismo externo --</option>
                                        @foreach($externalAgencies as $agency)
                                            <option value="{{ $agency }}">{{ $agency }}</option>
                                        @endforeach
                                        <option value="OTRO">OTRO ORGANISMO EXTERNO (Especificar)</option>
                                    </select>
                                    @error('external_agency') <span class="text-red-600 text-xs mt-1 block font-semibold">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-indigo-900 mb-1">N° Parte / Unidad / Contacto Externo (Opcional)</label>
                                    <input type="text"
                                           wire:model="external_reference"
                                           placeholder="Ej: 15° Comisaría, 1ra Cía Bomberos, Cuadrilla CGE..."
                                           class="w-full rounded-lg border-indigo-300 bg-white shadow-sm text-sm">
                                    @error('external_reference') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            @if($external_agency === 'OTRO')
                                <div>
                                    <label class="block text-xs font-bold text-indigo-900 mb-1">Especifique el Organismo Externo *</label>
                                    <input type="text"
                                           wire:model="custom_external_agency"
                                           placeholder="Ej: SAPU Buin, SAG, Senapred..."
                                           class="w-full rounded-lg border-indigo-300 bg-white shadow-sm text-sm">
                                    @error('custom_external_agency') <span class="text-red-600 text-xs mt-1 block font-semibold">{{ $message }}</span> @enderror
                                </div>
                            @endif

                            <label class="flex items-start gap-2.5 pt-1 cursor-pointer select-none">
                                <input type="checkbox"
                                       wire:model.live="close_after_derivation"
                                       class="mt-0.5 rounded border-indigo-400 text-emerald-600 focus:ring-emerald-500">
                                <span class="text-xs text-indigo-950">
                                    <strong class="font-extrabold">Cerrar el procedimiento al registrar esta derivación</strong>
                                    <span class="block text-[11px] text-indigo-700">
                                        Si lo dejas desmarcado, el caso quedará en estado <strong>Derivado</strong> (abierto en seguimiento). Si lo marcas, pasa a <strong>Cerrado</strong> y libera el móvil asignado.
                                    </span>
                                </span>
                            </label>
                        </div>
                    @endif

                    {{-- BLOQUE CONTEXTUAL: CIERRE DE PROCEDIMIENTO --}}
                    @if($isClosure)
                        <div class="p-4 rounded-xl bg-emerald-50/70 border border-emerald-200 space-y-2.5">
                            <div class="flex items-center gap-2 text-xs font-black uppercase text-emerald-800">
                                <i class="fa-solid fa-flag-checkered" aria-hidden="true"></i>
                                <span>Resolución Oficial de Cierre</span>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-emerald-900 mb-1">Resultado / Resolución Final *</label>
                                <select wire:model.live="closure_resolution" class="w-full rounded-lg border-emerald-300 bg-white shadow-sm text-sm font-semibold text-slate-800">
                                    <option value="">-- Seleccione resolución de cierre --</option>
                                    @foreach($closureResolutions as $resOption)
                                        <option value="{{ $resOption }}">{{ $resOption }}</option>
                                    @endforeach
                                </select>
                                @error('closure_resolution') <span class="text-red-600 text-xs mt-1 block font-semibold">{{ $message }}</span> @enderror
                            </div>
                            <p class="text-[11px] font-semibold text-emerald-800">
                                Al guardar, el procedimiento quedará <strong>CERRADO</strong>, se registrará la hora de cierre y se liberará la unidad asignada.
                            </p>
                        </div>
                    @endif

                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600">
                            Descripción Detallada de la Actuación
                            @if($isDerivation || $isClosure)
                                <span class="text-[10px] font-semibold text-slate-400 normal-case">(Opcional si el detalle ya queda cubierto arriba)</span>
                            @endif
                        </label>
                        <textarea wire:model="description" rows="3" class="mt-1 w-full rounded-lg border-slate-300 shadow-sm text-sm" placeholder="{{ $descriptionPlaceholder }}"></textarea>
                        @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4 pt-3 border-t border-slate-100">
                        <div class="space-y-2 flex-1">
                            <div class="flex flex-wrap items-center gap-3">
                                <label class="text-xs font-bold text-slate-700">📷 Evidencias (Opcional, múltiples fotos):</label>
                                <input type="file"
                                       wire:model="photos"
                                       multiple
                                       accept="image/*"
                                       class="text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            </div>

                            <div wire:loading wire:target="photos" class="text-xs font-semibold text-blue-600">
                                <i class="fa-solid fa-circle-notch fa-spin mr-1" aria-hidden="true"></i> Cargando vista previa de imágenes...
                            </div>

                            @if (! empty($photos))
                                <div class="flex flex-wrap gap-2 pt-1">
                                    @foreach ($photos as $pIdx => $tempPhoto)
                                        <div wire:key="pending-photo-{{ $pIdx }}" class="relative group">
                                            <img src="{{ $tempPhoto->temporaryUrl() }}" class="h-14 w-14 object-cover rounded-lg shadow border border-slate-200">
                                            <button type="button"
                                                    wire:click="removePendingPhoto({{ $pIdx }})"
                                                    aria-label="Quitar imagen"
                                                    class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-red-600 text-white text-[10px] font-black inline-flex items-center justify-center shadow hover:bg-red-700">
                                                ✕
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @error('photos') <span class="text-red-500 text-xs block">{{ $message }}</span> @enderror
                            @error('photos.*') <span class="text-red-500 text-xs block">{{ $message }}</span> @enderror
                            @error('photo') <span class="text-red-500 text-xs block">{{ $message }}</span> @enderror
                        </div>

                        <button type="submit" class="w-full sm:w-auto {{ $submitBtnClass }} text-white font-black py-2.5 px-6 rounded-lg shadow text-sm transition shrink-0">
                            {{ $submitLabel }}
                        </button>
                    </div>
                </form>
            </div>
            @else
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl shadow-sm font-bold text-center text-sm">
                🔒 Este procedimiento se encuentra CERRADO. No se pueden agregar más anotaciones regulares.
            </div>
            @endif

            {{-- HISTORIAL DE ACCIONES (BITÁCORA) --}}
            <div class="bg-white shadow-sm rounded-xl p-6 border border-slate-200">
                <h3 class="text-base font-black text-slate-800 border-b pb-2 mb-4">⏱️ Historial Cronológico de Acciones ({{ $procedure->logs->count() }})</h3>
                
                <div class="relative border-l-2 border-slate-200 ml-3 space-y-5">
                    @foreach($procedure->logs->sortByDesc('created_at') as $log)
                    @php
                        $canEditThisLog = auth()->check() && ((int) $log->user_id === (int) auth()->id() || auth()->user()->hasRole('Administrador'));
                    @endphp
                    <div class="ml-6 relative" wire:key="proc-log-{{ $log->id }}">
                        <span class="absolute flex items-center justify-center w-5 h-5 bg-blue-100 rounded-full -left-[35px] top-0.5 ring-4 ring-white">
                            <span class="w-2.5 h-2.5 bg-blue-600 rounded-full"></span>
                        </span>
                        
                        <div class="bg-slate-50 rounded-lg p-3.5 border border-slate-200/80">
                            <div class="flex justify-between items-start mb-1 gap-2">
                                <h4 class="font-black text-slate-800 text-sm">{{ $log->action_type }}</h4>
                                <div class="flex items-center gap-2">
                                    @if($canEditThisLog && $procedure->status !== 'Cerrado' && $editing_log_id !== $log->id)
                                        <button type="button"
                                                wire:click="startEditingLog({{ $log->id }})"
                                                class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 hover:underline">
                                            ✏️ Editar
                                        </button>
                                    @endif
                                    <span class="text-xs font-mono font-bold bg-white border px-2 py-0.5 rounded text-slate-600">
                                        {{ $log->created_at->format('d/m H:i:s') }}
                                    </span>
                                </div>
                            </div>

                            @if($editing_log_id === $log->id)
                                <div class="mt-2 space-y-2">
                                    <textarea wire:model="editing_log_description" rows="2" class="w-full rounded-lg border-indigo-300 text-sm"></textarea>
                                    @error('editing_log_description') <span class="text-red-500 text-xs block">{{ $message }}</span> @enderror
                                    <div class="flex justify-end gap-2">
                                        <button type="button" wire:click="cancelEditingLog" class="px-2.5 py-1 rounded border border-slate-300 bg-white text-xs font-bold text-slate-700">
                                            Cancelar
                                        </button>
                                        <button type="button" wire:click="saveEditedLog" class="px-3 py-1 rounded bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold">
                                            Guardar Cambios
                                        </button>
                                    </div>
                                </div>
                            @else
                                <p class="text-sm text-slate-700 mb-1.5">{{ $log->description }}</p>
                            @endif

                            <p class="text-[11px] text-slate-400 font-bold">Registrado por: {{ $log->user->name ?? 'Sistema' }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>

    {{-- MODAL BUSCADOR DE CASO PRINCIPAL PARA AGRUPAR DUPLICADOS --}}
    @if($isParentLinkModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
             role="dialog"
             aria-modal="true"
             aria-labelledby="link-parent-modal-title"
             @keydown.escape.window="$wire.closeParentLinkModal()">
            <div class="relative bg-white rounded-2xl max-w-3xl w-full shadow-2xl border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 bg-slate-900 text-white flex justify-between items-center border-b border-white/10">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-widest text-brand-lime block">
                            AGRUPACIÓN DE CASOS DUPLICADOS
                        </span>
                        <h3 id="link-parent-modal-title" class="text-base font-black flex items-center gap-2">
                            <i class="fa-solid fa-code-merge text-indigo-400" aria-hidden="true"></i>
                            <span>Anexar Caso #{{ $procedure->id }} a un Caso Principal</span>
                        </h3>
                    </div>
                    <button type="button"
                            wire:click="closeParentLinkModal"
                            aria-label="Cerrar buscador de caso principal"
                            class="text-gray-300 hover:text-white p-1 rounded-md focus:outline-none">
                        <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    @if($procedure->device)
                        <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-xs font-semibold text-amber-900 flex items-start gap-2">
                            <i class="fa-solid fa-triangle-exclamation text-amber-600 mt-0.5" aria-hidden="true"></i>
                            <span>
                                Atención: este caso (#{{ $procedure->id }}) tiene asignado el móvil <strong>{{ $procedure->device->name }}</strong>. Al anexarlo a un caso principal, este folio se cerrará y el móvil quedará liberado de este duplicado.
                            </span>
                        </div>
                    @endif

                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-700 mb-1">
                            <i class="fa-solid fa-magnifying-glass text-slate-400 mr-1" aria-hidden="true"></i>
                            Buscar Caso Principal (por Folio #, Dirección, Sector, Tipo o Solicitante)
                        </label>
                        <input type="text"
                               wire:model.live.debounce.300ms="parentLinkSearch"
                               placeholder="Ej: 45, Balmaceda, Robo, Sector Centro..."
                               class="w-full text-xs rounded-lg border-slate-300 py-2 shadow-xs focus:ring-indigo-500 focus:border-indigo-500">
                        @error('parent_id_to_link') <span class="text-red-500 text-xs mt-1 block font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 cursor-pointer">
                            <input type="checkbox" wire:model.live="showClosedParentsInLink" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span>Incluir casos cerrados</span>
                        </label>
                        <span class="text-[11px] text-slate-500">
                            Se priorizan casos activos del mismo sector ({{ $procedure->sector->name ?? 'N/A' }}).
                        </span>
                    </div>

                    <div class="max-h-72 overflow-y-auto space-y-2 border border-slate-200 rounded-xl p-2 bg-slate-50/50">
                        @forelse($candidateParents as $cand)
                            @php
                                $sameSector = (int) $cand->sector_id === (int) $procedure->sector_id;
                                $isClosedCand = $cand->status === 'Cerrado';
                            @endphp
                            <div wire:key="cand-parent-{{ $cand->id }}" class="bg-white p-3 rounded-lg border {{ $sameSector && ! $isClosedCand ? 'border-emerald-300 ring-1 ring-emerald-200' : 'border-slate-200' }} shadow-xs flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 hover:border-indigo-300 transition">
                                <div class="space-y-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-black text-sm text-slate-900">#{{ $cand->id }}</span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-indigo-100 text-indigo-800">
                                            {{ $cand->procedureType->name ?? 'Caso' }}
                                        </span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $isClosedCand ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700' }}">
                                            {{ $cand->status }}
                                        </span>
                                        @if($sameSector)
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-100 text-emerald-800">
                                                Mismo Sector
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-slate-700 font-medium">
                                        <i class="fa-solid fa-location-dot text-slate-400 text-[10px]" aria-hidden="true"></i>
                                        {{ $cand->exact_address ?: 'Sin dirección' }}
                                        @if($cand->sector)
                                            <span class="text-slate-500 font-normal">({{ $cand->sector->name }})</span>
                                        @endif
                                    </div>
                                    <div class="text-[11px] text-slate-500">
                                        Solicitante: <span class="font-bold">{{ $cand->caller_name ?: 'Anónimo' }}</span>
                                        • {{ $cand->created_at->format('d/m/Y H:i') }}
                                    </div>
                                </div>

                                <button type="button"
                                        wire:click="linkToParent({{ $cand->id }})"
                                        wire:confirm="¿Confirmas anexar y cerrar el Caso #{{ $procedure->id }} dentro del Caso Principal #{{ $cand->id }}?"
                                        class="w-full sm:w-auto shrink-0 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs px-3 py-2 rounded-lg shadow-xs transition inline-flex items-center justify-center gap-1.5">
                                    <i class="fa-solid fa-link" aria-hidden="true"></i>
                                    <span>Anexar Aquí</span>
                                </button>
                            </div>
                        @empty
                            <div class="py-8 text-center text-slate-500 text-xs">
                                <i class="fa-solid fa-folder-open text-2xl mb-1 block text-slate-300" aria-hidden="true"></i>
                                No se encontraron casos principales disponibles con el filtro actual.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="px-6 py-3 bg-slate-50 border-t border-slate-200 flex justify-end">
                    <button type="button"
                            wire:click="closeParentLinkModal"
                            class="px-4 py-2 bg-white border border-slate-300 rounded-lg text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>