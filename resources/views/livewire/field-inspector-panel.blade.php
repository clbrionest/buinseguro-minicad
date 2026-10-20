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

    {{-- ENCABEZADO MODO TERRENO --}}
    <div class="bg-gray-900 text-white rounded-xl shadow-lg p-4 mb-4">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <div>
                <span class="text-[11px] font-black uppercase tracking-widest text-indigo-400 block">MODO INSPECTOR / PATRULLA EN TERRENO</span>
                <h2 class="text-xl font-black flex items-center gap-2">
                    🚓 Consola Móvil Operativa
                </h2>
            </div>

            <div class="w-full sm:w-auto flex items-center gap-2">
                <select wire:model.live="selected_device_id" class="w-full sm:w-56 rounded-lg border-gray-700 bg-gray-800 text-white font-bold text-sm focus:ring-indigo-500">
                    @foreach($devices as $dev)
                        <option value="{{ $dev->id }}">
                            🚓 {{ $dev->name }} ({{ $dev->effective_status }})
                        </option>
                    @endforeach
                </select>
                <button type="button" @click="sendUnitGps()" :disabled="sendingGps" class="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-bold text-xs px-3 py-2.5 rounded-lg shadow whitespace-nowrap transition">
                    <span x-show="!sendingGps">📡 Enviar GPS</span>
                    <span x-show="sendingGps">⏳ GPS...</span>
                </button>
            </div>
        </div>

        @if($currentDevice)
            <div class="mt-3 pt-3 border-t border-gray-800 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 text-xs text-gray-300">
                <div>
                    @if($currentDevice->activePatrolAssignment)
                        Sector: <strong class="text-white">{{ $currentDevice->activePatrolAssignment->sector->name ?? 'General' }}</strong>
                        · Tripulación: <strong class="text-white">{{ $currentDevice->activePatrolAssignment->crew->pluck('name')->join(', ') ?: 'Sin asignar' }}</strong>
                    @else
                        <span class="text-amber-400">⚠️ Sin turno activo registrado en sistema para este móvil.</span>
                    @endif
                </div>
                @if($currentDevice->last_location_at)
                    <span class="text-emerald-400 font-semibold">
                        Último GPS: {{ $currentDevice->last_location_at->format('H:i:s') }}
                    </span>
                @endif
            </div>

            {{-- BOTONES RÁPIDOS DE ESTADO QAP --}}
            <div class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-2">
                @php
                    $statuses = [
                        'Disponible (QAP)' => ['icon' => '🟢', 'active' => 'bg-green-600 text-white ring-2 ring-green-300', 'inactive' => 'bg-gray-800 text-gray-300 hover:bg-gray-700'],
                        'Punto Fijo'       => ['icon' => '🟣', 'active' => 'bg-purple-600 text-white ring-2 ring-purple-300', 'inactive' => 'bg-gray-800 text-gray-300 hover:bg-gray-700'],
                        'En Colación'      => ['icon' => '🟡', 'active' => 'bg-amber-600 text-white ring-2 ring-amber-300', 'inactive' => 'bg-gray-800 text-gray-300 hover:bg-gray-700'],
                        'En Mantención'    => ['icon' => '🔴', 'active' => 'bg-red-600 text-white ring-2 ring-red-300', 'inactive' => 'bg-gray-800 text-gray-300 hover:bg-gray-700'],
                    ];
                    $currentBase = $currentDevice->operational_status ?: 'Disponible (QAP)';
                @endphp
                @foreach($statuses as $stName => $cfg)
                    <button type="button"
                            wire:click="setDeviceStatus('{{ $stName }}')"
                            class="py-2 px-2 rounded-lg text-xs font-black transition flex items-center justify-center gap-1.5 {{ $currentBase === $stName ? $cfg['active'] : $cfg['inactive'] }}">
                        <span>{{ $cfg['icon'] }}</span>
                        <span>{{ $stName }}</span>
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    @if (session()->has('field_message'))
        <div class="bg-green-100 border-l-4 border-green-600 text-green-900 p-3 mb-4 rounded-lg shadow-sm text-sm font-bold">
            {{ session('field_message') }}
        </div>
    @endif

    {{-- SECCIÓN 1: PROCEDIMIENTOS ASIGNADOS AL MÓVIL --}}
    <div class="mb-6">
        <div class="flex justify-between items-center mb-3">
            <h3 class="text-base font-black text-gray-800 uppercase tracking-wide">
                🚨 Procedimientos Asignados ({{ $assignedProcedures->count() }})
            </h3>
            <span class="text-xs text-gray-500">Actualización cada 10s</span>
        </div>

        <div class="space-y-4">
            @forelse($assignedProcedures as $proc)
                @php
                    $sla = $proc->sla_traffic_light;
                    $navQuery = ($proc->latitude && $proc->longitude)
                        ? "{$proc->latitude},{$proc->longitude}"
                        : urlencode($proc->exact_address . ', Buin, Chile');
                @endphp
                <div class="bg-white rounded-xl shadow-md border-l-4 {{ $proc->status === 'En Camino' ? 'border-yellow-500' : 'border-blue-600' }} p-4">
                    <div class="flex justify-between items-start gap-2 mb-2">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-black bg-gray-900 text-white px-2 py-0.5 rounded">#{{ $proc->id }}</span>
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $proc->status === 'En Camino' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800' }}">
                                    {{ $proc->status }}
                                </span>
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full border {{ $sla['badge'] }}">
                                    {{ $sla['label'] }}
                                </span>
                            </div>
                            <h4 class="text-lg font-black text-gray-900 mt-1">{{ $proc->procedureType->name ?? 'Procedimiento' }}</h4>
                        </div>
                        <span class="text-xs font-bold text-gray-500">{{ $proc->created_at->format('H:i') }}</span>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-200 text-sm space-y-1 mb-3">
                        <p class="font-bold text-gray-800">📍 {{ $proc->sector->name ?? 'Sector' }} — {{ $proc->exact_address }}</p>
                        @if($proc->address_reference)
                            <p class="text-xs text-gray-600"><strong>Ref:</strong> {{ $proc->address_reference }}</p>
                        @endif
                        @if($proc->caller_name || $proc->caller_phone)
                            <p class="text-xs text-gray-600 pt-1 border-t border-gray-200">
                                <strong>Informante:</strong> {{ $proc->caller_name ?: 'Anónimo' }}
                                @if($proc->caller_phone)
                                    · <a href="tel:{{ $proc->caller_phone }}" class="text-blue-600 font-bold underline">{{ $proc->caller_phone }}</a>
                                @endif
                            </p>
                        @endif
                        @php $fieldMeta = $proc->formatted_metadata; @endphp
                        @if(count($fieldMeta) > 0)
                            <div class="pt-1.5 border-t border-gray-200 flex flex-wrap gap-1">
                                @foreach($fieldMeta as $fm)
                                    <span class="text-[11px] bg-indigo-50 text-indigo-900 border border-indigo-200 px-2 py-0.5 rounded font-semibold">
                                        <strong>{{ $fm['label'] }}:</strong> {{ $fm['value'] }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- ACCIONES TÁCTILES DE TERRENO --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        {{-- 1. Navegación GPS --}}
                        <div class="flex gap-1.5">
                            <a href="https://www.google.com/maps/dir/?api=1&destination={{ $navQuery }}" target="_blank"
                               class="flex-1 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 font-bold py-2.5 px-3 rounded-lg text-xs text-center flex items-center justify-center gap-1">
                                🗺️ Maps
                            </a>
                            @if($proc->latitude && $proc->longitude)
                                <a href="https://waze.com/ul?ll={{ $proc->latitude }},{{ $proc->longitude }}&navigate=yes" target="_blank"
                                   class="flex-1 bg-cyan-50 hover:bg-cyan-100 text-cyan-800 border border-cyan-200 font-bold py-2.5 px-3 rounded-lg text-xs text-center flex items-center justify-center gap-1">
                                    🚗 Waze
                                </a>
                            @endif
                        </div>

                        {{-- 2. Confirmación Llegada GPS --}}
                        <div>
                            @if($proc->arrived_at)
                                <div class="w-full bg-green-100 text-green-800 border border-green-300 font-bold py-2.5 px-3 rounded-lg text-xs text-center">
                                    ✓ En el Lugar ({{ $proc->arrived_at->format('H:i') }})
                                </div>
                            @elseif($proc->mobile_arrived_at)
                                <div class="w-full bg-amber-100 text-amber-900 border border-amber-300 font-bold py-2.5 px-3 rounded-lg text-xs text-center">
                                    📡 GPS Enviado ({{ $proc->mobile_arrived_at->format('H:i') }}) · Esp. Central
                                </div>
                            @else
                                <button type="button"
                                        @click="markFieldArrival({{ $proc->id }})"
                                        :disabled="arrivingCaseId === {{ $proc->id }}"
                                        class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-black py-2.5 px-3 rounded-lg shadow text-xs flex items-center justify-center gap-1.5">
                                    <span x-show="arrivingCaseId !== {{ $proc->id }}">📍 Marcar Llegada (GPS)</span>
                                    <span x-show="arrivingCaseId === {{ $proc->id }}">⏳ Capturando GPS...</span>
                                </button>
                            @endif
                        </div>

                        {{-- 3. Novedad / Foto Rápida o Ficha Completa --}}
                        <div class="flex gap-1.5">
                            <button type="button" wire:click="openQuickNote({{ $proc->id }})"
                                    class="flex-1 bg-gray-800 hover:bg-gray-900 text-white font-bold py-2.5 px-3 rounded-lg text-xs">
                                📷 Nota / Foto
                            </button>
                            <a href="{{ route('procedures.show', $proc->id) }}"
                               class="bg-gray-100 hover:bg-gray-200 text-gray-700 border font-bold py-2.5 px-3 rounded-lg text-xs flex items-center justify-center">
                                Ficha →
                            </a>
                        </div>
                    </div>

                    {{-- FORMULARIO RÁPIDO DESPLEGABLE PARA FOTO / NOVEDAD EN TERRENO --}}
                    @if($active_procedure_for_note === $proc->id)
                        <form wire:submit.prevent="saveQuickNote({{ $proc->id }})" class="mt-3 pt-3 border-t border-gray-200 bg-indigo-50/50 p-3 rounded-lg space-y-2">
                            <label class="block text-xs font-bold text-gray-700 uppercase">Enviar Reporte / Fotografía desde Terreno</label>
                            <textarea wire:model="quick_note" rows="2" class="w-full rounded-md border-gray-300 text-sm" placeholder="Escriba la novedad observada en el lugar..."></textarea>
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                                <input type="file" wire:model="quick_photo" accept="image/*" capture="environment" class="text-xs text-gray-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-bold file:bg-indigo-100 file:text-indigo-800">
                                <div class="flex gap-2 w-full sm:w-auto justify-end">
                                    <button type="button" wire:click="openQuickNote({{ $proc->id }})" class="text-xs text-gray-600 px-3 py-1.5">Cancelar</button>
                                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-4 py-1.5 rounded shadow">
                                        Enviar a Central
                                    </button>
                                </div>
                            </div>
                            @error('quick_note') <span class="text-red-500 text-xs block">{{ $message }}</span> @enderror
                            @error('quick_photo') <span class="text-red-500 text-xs block">{{ $message }}</span> @enderror
                        </form>
                    @endif
                </div>
            @empty
                <div class="bg-white rounded-xl border p-8 text-center text-gray-500 shadow-sm">
                    <p class="text-base font-bold text-gray-700">Sin procedimientos activos asignados a este móvil</p>
                    <p class="text-xs mt-1">Manténgase atento a las instrucciones de Central o en patrullaje preventivo.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- SECCIÓN 2: REPORTES DE CONTINGENCIA / EVENTOS MASIVOS ASIGNADOS AL MÓVIL --}}
    @if($assignedEventReports->count() > 0)
        <div class="mb-6">
            <h3 class="text-base font-black text-amber-800 uppercase tracking-wide mb-3">
                🌩️ Reportes de Contingencia Asignados ({{ $assignedEventReports->count() }})
            </h3>
            <div class="space-y-3">
                @foreach($assignedEventReports as $rep)
                    <div class="bg-amber-50 border-l-4 border-amber-500 rounded-xl p-4 shadow-sm flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-black bg-amber-200 text-amber-900 px-2 py-0.5 rounded">{{ $rep->quick_category }}</span>
                                <span class="text-xs font-bold text-gray-600">{{ $rep->event->name ?? 'Contingencia' }}</span>
                            </div>
                            <p class="font-bold text-gray-900 text-sm mt-1">📍 {{ $rep->sector->name ?? '' }} — {{ $rep->exact_address }}</p>
                            @if($rep->notes)
                                <p class="text-xs text-gray-600 mt-0.5">{{ $rep->notes }}</p>
                            @endif
                        </div>
                        <button type="button" wire:click="resolveEventReportFromField({{ $rep->id }})"
                                class="w-full sm:w-auto bg-green-600 hover:bg-green-700 text-white font-bold text-xs px-4 py-2.5 rounded-lg shadow whitespace-nowrap">
                            ✓ Marcar Resuelto
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>
