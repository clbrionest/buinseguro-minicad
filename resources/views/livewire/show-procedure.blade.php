<div class="py-6 max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
    
    {{-- BARRA SUPERIOR DE COMANDO DEL PROCEDIMIENTO --}}
    <div class="bg-slate-900 text-white rounded-xl shadow-lg p-4 mb-6 flex flex-wrap justify-between items-center gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('dashboard') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 px-3 py-1.5 rounded-lg text-xs font-bold transition">
                &larr; Panel Central
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-black bg-blue-600 text-white px-2.5 py-0.5 rounded">FOLIO #{{ $procedure->id }}</span>
                    <span class="text-xs font-bold text-slate-300">{{ $procedure->created_at->format('d/m/Y H:i:s') }}</span>
                    @php
                        $statusBadge = match($procedure->status) {
                            'Pendiente' => 'bg-red-600 text-white',
                            'En Camino' => 'bg-amber-500 text-slate-900',
                            'En el Lugar' => 'bg-blue-500 text-white',
                            'Derivado' => 'bg-orange-500 text-white',
                            'Cerrado' => 'bg-emerald-600 text-white',
                            default => 'bg-slate-600 text-white',
                        };
                    @endphp
                    <span class="text-xs font-black px-2.5 py-0.5 rounded-full {{ $statusBadge }}">
                        {{ $procedure->status }}
                    </span>
                </div>
                <h2 class="text-xl font-black mt-1">
                    {{ $procedure->procedureType->name ?? 'Procedimiento' }} — <span class="text-slate-300 font-semibold">{{ $procedure->sector->name ?? '' }}</span>
                </h2>
            </div>
        </div>

        @php $sla = $procedure->sla_traffic_light; @endphp
        <div class="flex items-center gap-3">
            <span class="border px-3 py-1.5 rounded-full text-xs font-bold flex items-center gap-1.5 {{ $sla['badge'] }}">
                <span class="w-2.5 h-2.5 rounded-full {{ $sla['dot'] }}"></span>
                SLA: {{ $sla['label'] }}
            </span>
            <a href="{{ route('procedures.print', $procedure->id) }}" target="_blank" class="bg-white hover:bg-slate-100 text-slate-900 text-xs font-black py-2 px-3.5 rounded-lg shadow flex items-center gap-1.5 transition">
                🖨️ Ficha Oficial / PDF
            </a>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-900 p-4 mb-6 rounded-lg shadow-sm font-bold text-sm">
            ✓ {{ session('message') }}
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
                        <span class="font-bold text-slate-500 block text-xs uppercase">Ubicación:</span>
                        <p class="font-bold text-slate-900">{{ $procedure->sector->name }} — {{ $procedure->exact_address }}</p>
                        @if($procedure->address_reference)
                            <p class="text-xs text-slate-600 mt-0.5"><strong>Ref:</strong> {{ $procedure->address_reference }}</p>
                        @endif
                    </div>
                    <div class="pt-2 border-t border-slate-100 flex justify-between items-center">
                        <span class="font-bold text-slate-500">Móvil Asignado:</span>
                        <span class="font-black {{ $procedure->device ? 'text-slate-900 bg-slate-100 px-2.5 py-0.5 rounded border' : 'text-red-600' }}">
                            {{ $procedure->device ? '🚓 ' . $procedure->device->name : 'Sin Asignar' }}
                        </span>
                    </div>
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
            </div>

            @if(!$procedure->parent_id && !in_array($procedure->status, ['Cerrado', 'Cancelado']))
            <div class="bg-slate-50 shadow-sm rounded-xl p-5 border border-slate-200">
                <h3 class="text-sm font-black uppercase text-slate-700 mb-1">🔗 Vincular Caso Duplicado</h3>
                <p class="text-xs text-slate-500 mb-3">Si es un reporte repetido del mismo incidente, anéxalo al caso principal:</p>
                
                <div class="flex gap-2">
                    <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-slate-300 bg-slate-200 text-slate-600 font-bold text-sm">#</span>
                    <input type="number" wire:model="parent_id_to_link" placeholder="ID Principal" class="flex-1 rounded-none rounded-r-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                    <button wire:click="linkToParent" onclick="confirm('¿Estás seguro de vincular y cerrar este caso?') || event.stopImmediatePropagation()" class="bg-slate-800 hover:bg-slate-900 text-white font-bold py-2 px-4 rounded-lg text-xs shadow-sm transition">
                        Vincular
                    </button>
                </div>
                @error('parent_id_to_link') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>
            @endif

            @if($procedure->evidences->count() > 0)
            <div class="bg-white shadow-sm rounded-xl p-5 border border-slate-200 border-t-4 border-t-indigo-500">
                <h3 class="text-sm font-black uppercase text-slate-700 border-b pb-2 mb-3">📷 Evidencia Fotográfica ({{ $procedure->evidences->count() }})</h3>
                <div class="grid grid-cols-2 gap-2">
                    @foreach($procedure->evidences as $evidence)
                        <a href="{{ asset('storage/' . $evidence->file_path) }}" target="_blank" class="block group relative">
                            <img src="{{ asset('storage/' . $evidence->file_path) }}" class="rounded-lg shadow hover:opacity-75 transition-opacity h-24 w-full object-cover">
                            <span class="absolute bottom-0 left-0 bg-black/60 text-white text-[10px] px-1.5 py-0.5 rounded-bl-lg rounded-tr">
                                {{ $evidence->created_at->format('d/m H:i') }}
                            </span>
                        </a>
                    @endforeach
                </div>
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
                        <div class="bg-slate-50 border rounded-lg p-2.5 text-xs">
                            <div class="flex justify-between items-start mb-1">
                                <a href="{{ route('procedures.show', $child->id) }}" target="_blank" class="font-bold text-blue-600 hover:underline">Caso #{{ $child->id }}</a>
                                <span class="text-slate-500">{{ $child->created_at->format('H:i') }}</span>
                            </div>
                            <p class="text-slate-700"><strong>Llama:</strong> {{ $child->caller_name ?: 'Anónimo' }} ({{ $child->caller_phone ?: 'S/T' }})</p>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if($procedure->call && $procedure->call->recording_path)
                <div class="p-4 bg-slate-900 rounded-xl shadow-inner">
                    <label class="text-xs text-emerald-400 font-bold uppercase mb-2 block">
                        🎙️ Grabación Telefónica PBX (Issabel)
                    </label>
                    <audio controls class="w-full">
                        <source src="{{ route('calls.play', $procedure->call->id) }}" type="audio/wav">
                        Tu navegador no soporta audio.
                    </audio>
                </div>
            @endif

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
                                <div class="bg-indigo-50/40 p-3 rounded-lg border border-indigo-100">
                                    <label class="block text-xs font-bold text-indigo-950 mb-1">
                                        {{ $field['label'] }}
                                    </label>
                                    @if(($field['type'] ?? 'text') === 'select')
                                        <select wire:model="metaValues.{{ $field['key'] }}" class="w-full rounded-lg border-indigo-200 bg-white text-sm font-semibold" @disabled($procedure->status === 'Cerrado')>
                                            <option value="">Sin especificar...</option>
                                            @foreach(array_filter(array_map('trim', explode(',', $field['options'] ?? ''))) as $opt)
                                                <option value="{{ $opt }}">{{ $opt }}</option>
                                            @endforeach
                                        </select>
                                    @elseif(($field['type'] ?? 'text') === 'boolean')
                                        <select wire:model="metaValues.{{ $field['key'] }}" class="w-full rounded-lg border-indigo-200 bg-white text-sm font-semibold" @disabled($procedure->status === 'Cerrado')>
                                            <option value="">Sin especificar</option>
                                            <option value="Sí">Sí</option>
                                            <option value="No">No</option>
                                        </select>
                                    @elseif(($field['type'] ?? 'text') === 'number')
                                        <input type="number" min="0" wire:model="metaValues.{{ $field['key'] }}" placeholder="{{ $field['placeholder'] ?? '0' }}" class="w-full rounded-lg border-indigo-200 bg-white text-sm font-semibold" @disabled($procedure->status === 'Cerrado')>
                                    @else
                                        <input type="text" wire:model="metaValues.{{ $field['key'] }}" placeholder="{{ $field['placeholder'] ?? 'Ingrese antecedente...' }}" class="w-full rounded-lg border-indigo-200 bg-white text-sm font-semibold" @disabled($procedure->status === 'Cerrado')>
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
            <div class="bg-white shadow-sm rounded-xl p-6 border border-slate-200">
                <h3 class="text-base font-black text-slate-800 mb-4">✍️ Registrar Actuación / Novedad en Bitácora</h3>
                <form wire:submit.prevent="saveLog">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Tipo de Acción</label>
                            <select wire:model="action_type" class="w-full rounded-lg border-slate-300 shadow-sm text-sm font-semibold">
                                @foreach($logActions as $actOption)
                                    <option value="{{ $actOption }}">{{ $actOption }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Resolución / Derivación Rápida</label>
                            <x-searchable-select
                                wireAction="applyResolution"
                                :options="$resolutions ?? []"
                                placeholder="-- Buscar resolución / derivación --"
                                searchPlaceholder="Digite resolución (ej: Carabineros, Enel, infracción)..."
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Estado Operativo del Caso</label>
                            <select wire:model="status" class="w-full rounded-lg border-slate-300 shadow-sm text-sm font-black text-blue-700">
                                <option value="Pendiente">Pendiente</option>
                                <option value="En Camino">En Camino</option>
                                <option value="En el Lugar">En el Lugar</option>
                                <option value="Derivado">Derivado</option>
                                <option value="Cerrado">Cerrado</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-xs font-bold uppercase text-slate-600">Descripción Detallada de la Actuación</label>
                        <textarea wire:model="description" rows="3" class="mt-1 w-full rounded-lg border-slate-300 shadow-sm text-sm" placeholder="Escriba los detalles reportados por la unidad o central..."></textarea>
                        @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pt-3 border-t border-slate-100">
                        <div class="flex items-center gap-3">
                            <label class="text-xs font-bold text-slate-700">📷 Evidencia (Opcional):</label>
                            <input type="file" wire:model="photo" accept="image/*" capture="environment" class="text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            @if ($photo)
                                <img src="{{ $photo->temporaryUrl() }}" class="h-12 w-auto rounded shadow border">
                            @endif
                            @error('photo') <span class="text-red-500 text-xs block">{{ $message }}</span> @enderror
                        </div>

                        <button type="submit" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-black py-2.5 px-6 rounded-lg shadow text-sm transition">
                            Guardar en Bitácora
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
                    <div class="ml-6 relative">
                        <span class="absolute flex items-center justify-center w-5 h-5 bg-blue-100 rounded-full -left-[35px] top-0.5 ring-4 ring-white">
                            <span class="w-2.5 h-2.5 bg-blue-600 rounded-full"></span>
                        </span>
                        
                        <div class="bg-slate-50 rounded-lg p-3.5 border border-slate-200/80">
                            <div class="flex justify-between items-start mb-1">
                                <h4 class="font-black text-slate-800 text-sm">{{ $log->action_type }}</h4>
                                <span class="text-xs font-mono font-bold bg-white border px-2 py-0.5 rounded text-slate-600">
                                    {{ $log->created_at->format('d/m H:i:s') }}
                                </span>
                            </div>
                            <p class="text-sm text-slate-700 mb-1.5">{{ $log->description }}</p>
                            <p class="text-[11px] text-slate-400 font-bold">Registrado por: {{ $log->user->name ?? 'Sistema' }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</div>