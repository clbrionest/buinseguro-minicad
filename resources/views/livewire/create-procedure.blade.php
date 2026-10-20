<div>
    <button wire:click="openModal" class="bg-blue-600 hover:bg-blue-700 text-white font-black py-2 px-4 rounded-lg shadow text-sm flex items-center gap-1.5 transition">
        <span>➕</span>
        <span>Nuevo Procedimiento</span>
    </button>

    @if($isOpen)
        <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs overflow-y-auto h-full w-full flex justify-center items-start pt-6 pb-10 z-50">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-5xl mx-4 border border-slate-200 overflow-hidden">
                
                <div class="flex justify-between items-center bg-slate-900 text-white px-6 py-4">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-widest text-blue-400 block">CONSOLA DE RECEPCIÓN Y DESPACHO CAD</span>
                        <h3 class="text-lg font-black">Ingreso de Nuevo Procedimiento</h3>
                    </div>
                    <button type="button" wire:click="closeModal" class="text-slate-400 hover:text-white focus:outline-none">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form wire:submit.prevent="save" class="p-6 space-y-5">
    
                    <div class="grid grid-cols-1 lg:grid-cols-6 gap-5">

                        <!-- 1. Categorización de Inicio (Al Llamar) -->
                        <div class="lg:col-span-2 bg-slate-50 p-4 rounded-xl border border-slate-200 flex flex-col justify-between">
                            <div>
                                <h4 class="font-black text-slate-800 text-sm mb-3 border-b border-slate-200 pb-1.5 flex items-center gap-1.5">
                                    🏷️ 1. Categorización de Inicio
                                </h4>

                                <label class="block text-xs font-bold uppercase text-slate-600">
                                    Tipo de Procedimiento (Al Llamar) *
                                </label>
                                <select wire:model.live="procedure_type_id"
                                    class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm mb-3 text-sm font-semibold focus:border-blue-500">
                                    <option value="">Seleccione tipificación inicial...</option>
                                    @foreach($procedureTypes as $type)
                                        <option value="{{ $type->id }}">
                                            {{ $type->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('procedure_type_id') <span class="text-red-500 text-xs block mb-2">Seleccione un tipo.</span> @enderror

                                <label class="block text-xs font-bold uppercase text-slate-600">
                                    Origen de Ingreso *
                                </label>
                                <select wire:model="origin_id"
                                    class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm">
                                    <option value="">Seleccione el origen...</option>
                                    @foreach($origins as $origin)
                                        <option value="{{ $origin->id }}">
                                            {{ $origin->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('origin_id') <span class="text-red-500 text-xs block mt-1">Seleccione el origen.</span> @enderror
                            </div>

                            <!-- Preventivo -->
                            <div class="mt-4 pt-3 border-t border-slate-200">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="checkbox"
                                        wire:model.live="is_preventive"
                                        class="rounded border-slate-300 text-blue-600 shadow-sm">
                                    <span class="ml-2 text-xs font-bold text-slate-700">
                                        Patrullaje Preventivo (Sin informante)
                                    </span>
                                </label>
                            </div>
                        </div>

                        <!-- 2. Ubicación Georreferenciada -->
                        <div class="lg:col-span-4 bg-slate-50 p-4 rounded-xl border border-slate-200">
                            <h4 class="font-black text-slate-800 text-sm mb-3 border-b border-slate-200 pb-1.5">
                                📍 2. Ubicación Georreferenciada
                            </h4>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                                <div>
                                    <label class="block text-xs font-bold uppercase text-slate-600">
                                        Sector Jurisdiccional *
                                    </label>
                                    <select wire:model="sector_id"
                                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm">
                                        <option value="">Seleccione un sector...</option>
                                        @foreach($sectors as $sector)
                                            <option value="{{ $sector->id }}">
                                                {{ $sector->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('sector_id') <span class="text-red-500 text-xs block mt-1">Seleccione el sector.</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-bold uppercase text-slate-600">
                                        Punto de Interés (Hito Rápido)
                                    </label>
                                    <select wire:model.live="landmark_id"
                                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-blue-700 font-semibold text-sm">
                                        <option value="">
                                            No es un hito conocido...
                                        </option>
                                        @foreach($landmarks as $landmark)
                                            <option value="{{ $landmark->id }}">
                                                {{ $landmark->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Google Maps -->
                            <div class="mb-3"
                                wire:ignore
                                x-data="{
                                    initAutocomplete() {
                                        if(typeof google === 'undefined') return;
                                        const input = this.$refs.searchInput;
                                        const options = {
                                            componentRestrictions: { country: 'cl' },
                                            fields: ['geometry', 'formatted_address', 'name'],
                                        };
                                        const autocomplete = new google.maps.places.Autocomplete(input, options);
                                        autocomplete.addListener('place_changed', () => {
                                            const place = autocomplete.getPlace();
                                            if (!place.geometry || !place.geometry.location) return;
                                            $wire.set('latitude', place.geometry.location.lat());
                                            $wire.set('longitude', place.geometry.location.lng());
                                            $wire.set('formatted_address', place.formatted_address);
                                            $wire.set('exact_address', place.name + ', ' + place.formatted_address);
                                        });
                                    }
                                }"
                                x-init="initAutocomplete()">

                                <label class="block text-xs font-bold uppercase text-slate-600">
                                    Buscador de Calle / Intersección (Google Maps)
                                </label>
                                <input x-ref="searchInput"
                                    type="text"
                                    class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"
                                    placeholder="Escribe calle, numeración o intersección en Buin...">
                            </div>

                            <!-- Dirección Exacta y Referencia -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold uppercase text-slate-600">
                                        Dirección Exacta Confirmada *
                                    </label>
                                    <input type="text"
                                        wire:model="exact_address"
                                        placeholder="Se autocompleta o escriba manualmente..."
                                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm bg-white text-sm font-medium">
                                    @error('exact_address')
                                        <span class="text-red-500 text-xs block mt-1">
                                            La dirección es obligatoria.
                                        </span>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-bold uppercase text-slate-600">
                                        Referencia / Block / Color Casa
                                    </label>
                                    <input type="text"
                                        wire:model="address_reference"
                                        placeholder="Ej: Frente a plaza, reja negra"
                                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm">
                                </div>
                            </div>
                        </div>

                        <!-- SECCIÓN DINÁMICA: ANTECEDENTES / METADATOS SEGÚN CATEGORÍA ELEGIDA -->
                        @if(count($currentMetaFields) > 0)
                            <div class="lg:col-span-6 bg-indigo-50/70 p-4 rounded-xl border border-indigo-200">
                                <div class="flex justify-between items-center mb-3 border-b border-indigo-200 pb-1.5">
                                    <h4 class="font-black text-indigo-950 text-sm flex items-center gap-1.5">
                                        🧩 Antecedentes Específicos de la Categoría (Metadatos Iniciales)
                                    </h4>
                                    <span class="text-[11px] font-bold bg-indigo-100 text-indigo-800 px-2.5 py-0.5 rounded-full">
                                        Se pueden completar ahora o actualizar al operar el caso
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                    @foreach($currentMetaFields as $field)
                                        <div>
                                            <label class="block text-xs font-bold text-indigo-900 mb-1">
                                                {{ $field['label'] }}
                                            </label>
                                            @if(($field['type'] ?? 'text') === 'select')
                                                <select wire:model="metaValues.{{ $field['key'] }}" class="w-full rounded-lg border-indigo-300 bg-white text-sm shadow-sm">
                                                    <option value="">Seleccione...</option>
                                                    @foreach(array_filter(array_map('trim', explode(',', $field['options'] ?? ''))) as $opt)
                                                        <option value="{{ $opt }}">{{ $opt }}</option>
                                                    @endforeach
                                                </select>
                                            @elseif(($field['type'] ?? 'text') === 'boolean')
                                                <select wire:model="metaValues.{{ $field['key'] }}" class="w-full rounded-lg border-indigo-300 bg-white text-sm shadow-sm">
                                                    <option value="">Sin especificar</option>
                                                    <option value="Sí">Sí</option>
                                                    <option value="No">No</option>
                                                </select>
                                            @elseif(($field['type'] ?? 'text') === 'number')
                                                <input type="number" min="0" wire:model="metaValues.{{ $field['key'] }}" placeholder="{{ $field['placeholder'] ?? '0' }}" class="w-full rounded-lg border-indigo-300 bg-white text-sm shadow-sm">
                                            @else
                                                <input type="text" wire:model="metaValues.{{ $field['key'] }}" placeholder="{{ $field['placeholder'] ?? '' }}" class="w-full rounded-lg border-indigo-300 bg-white text-sm shadow-sm">
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- 3. Informante -->
                        @if(!$is_preventive)
                        <div class="lg:col-span-4 bg-slate-50 p-4 rounded-xl border border-slate-200">
                            <div class="flex justify-between items-center mb-3 border-b border-slate-200 pb-1.5">
                                <h4 class="font-black text-slate-800 text-sm">
                                    👤 3. Datos del Informante y Relato Inicial
                                </h4>
                                @if($callerStats['total_cases'] > 0 || $callerStats['total_calls'] > 0)
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs font-bold px-2 py-0.5 rounded bg-blue-100 text-blue-800">
                                            {{ $callerStats['total_cases'] }} casos previos
                                        </span>
                                        <span class="text-xs font-bold px-2 py-0.5 rounded bg-slate-200 text-slate-700">
                                            {{ $callerStats['total_calls'] }} llamadas PBX
                                        </span>
                                        @if($callerStats['pranks'] > 0)
                                            <span class="text-xs font-bold px-2 py-0.5 rounded bg-red-100 text-red-700 animate-pulse">
                                                ⚠️ {{ $callerStats['pranks'] }} Pitanza/Broma
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                                <div>
                                    <label class="block text-xs font-bold uppercase text-slate-600">
                                        Teléfono
                                    </label>
                                    <input type="text"
                                        wire:model.live.debounce.400ms="caller_phone"
                                        placeholder="Ej: +569..."
                                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm">
                                </div>

                                <div>
                                    <label class="block text-xs font-bold uppercase text-slate-600">
                                        RUT / Pasaporte
                                    </label>
                                    <input type="text"
                                        wire:model="caller_id_document"
                                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm">
                                </div>

                                <div>
                                    <label class="block text-xs font-bold uppercase text-slate-600">
                                        Nombre Informante
                                    </label>
                                    <input type="text"
                                        wire:model="caller_name"
                                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase text-slate-600">
                                    Relato / Observación Inicial del Llamado
                                </label>
                                <textarea wire:model="initial_description" rows="2" placeholder="Describa brevemente lo reportado por el vecino..." class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm"></textarea>
                            </div>

                            @if(count($callerHistory) > 0)
                                <div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                    <p class="text-xs font-bold text-blue-900 uppercase mb-2">
                                        📋 Historial Previo Asociado a {{ $caller_phone }}:
                                    </p>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 max-h-28 overflow-y-auto">
                                        @foreach($callerHistory as $hist)
                                            <div class="bg-white p-2 rounded border border-blue-100 text-xs flex justify-between items-center">
                                                <div class="truncate pr-2">
                                                    <a href="{{ route('procedures.show', $hist['id']) }}" target="_blank" class="font-bold text-blue-700 hover:underline">
                                                        #{{ $hist['id'] }} - {{ $hist['type'] }}
                                                    </a>
                                                    <div class="text-[11px] text-slate-500 truncate">{{ $hist['sector'] }} · {{ $hist['address'] }}</div>
                                                </div>
                                                <div class="text-right whitespace-nowrap">
                                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">{{ $hist['status'] }}</span>
                                                    <div class="text-[10px] text-slate-400 mt-0.5">{{ $hist['date'] }}</div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                        @endif

                        <!-- 4. Asignación Inmediata -->
                        <div class="{{ $is_preventive ? 'lg:col-span-6' : 'lg:col-span-2' }} bg-blue-50 p-4 rounded-xl border border-blue-200">
                            <h4 class="font-black text-blue-900 text-sm mb-3 border-b border-blue-200 pb-1.5">
                                🚓 4. Despacho de Unidad
                            </h4>

                            <label class="block text-xs font-bold uppercase text-blue-900">
                                Despachar Móvil (Opcional)
                            </label>
                            <select wire:model="device_id"
                                class="mt-1 block w-full rounded-lg border-blue-300 shadow-sm text-sm font-semibold">
                                <option value="">
                                    ⏳ Sin asignar (Dejar Pendiente en Sala)
                                </option>
                                @foreach($devices as $device)
                                    <option value="{{ $device->id }}">
                                        {{ $device->name }} — [{{ $device->effective_status }}{{ $device->active_procedures_count > 0 ? ' · ' . $device->active_procedures_count . ' caso(s)' : '' }}]
                                    </option>
                                @endforeach
                            </select>

                            @if($is_preventive)
                                <div class="mt-3">
                                    <label class="block text-xs font-bold uppercase text-blue-900">
                                        Detalle del Patrullaje Preventivo
                                    </label>
                                    <textarea wire:model="initial_description" rows="2" placeholder="Indique objetivo o punto focal del patrullaje preventivo..." class="mt-1 block w-full rounded-lg border-blue-300 shadow-sm text-sm"></textarea>
                                </div>
                            @endif
                        </div>

                    </div>

                    <!-- Botones -->
                    <div class="flex justify-end space-x-3 border-t border-slate-200 pt-4">
                        <button type="button"
                            wire:click="closeModal"
                            class="bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold py-2.5 px-5 rounded-lg text-sm transition">
                            Cancelar
                        </button>

                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-black py-2.5 px-6 rounded-lg shadow text-sm transition">
                            ✓ Registrar Procedimiento
                        </button>
                    </div>

                </form>
            </div>
        </div>
    @endif
    
</div>