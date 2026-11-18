<div>
    @if($showButton)
        <button type="button"
                wire:click="openModal"
                class="bg-brand-lime hover:bg-brand-lime-mid text-brand-black font-extrabold py-2 px-4 rounded-lg shadow-xs text-xs inline-flex items-center gap-1.5 transition min-h-[38px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ui-focus">
            <i class="fa-solid fa-plus" aria-hidden="true"></i>
            <span>Crear Procedimiento</span>
        </button>
    @endif

    @if($isOpen)
        <div class="fixed inset-0 bg-black/70 backdrop-blur-xs overflow-y-auto h-full w-full flex justify-center items-start pt-6 pb-10 z-50" role="dialog" aria-modal="true" aria-labelledby="modal-new-proc-title">
            <div class="bg-ui-card text-ui-fg rounded-xl shadow-2xl w-full max-w-5xl mx-4 border border-ui-border">
                
                <div class="flex justify-between items-center bg-ui-sidebar text-brand-white px-6 py-4 rounded-t-xl border-b border-white/10">
                    <div>
                        <span class="text-[10px] font-extrabold uppercase tracking-widest text-brand-lime-light block">CONSOLA DE RECEPCIÓN Y DESPACHO CAD</span>
                        <h3 id="modal-new-proc-title" class="text-lg font-extrabold flex items-center gap-2 flex-wrap">
                            <span>Ingreso de Nuevo Procedimiento</span>
                            @if($preventivePatrolFolio)
                                <span class="bg-brand-lime text-brand-black text-[11px] font-black px-2.5 py-0.5 rounded-full inline-flex items-center gap-1 shadow-xs">
                                    <i class="fa-solid fa-shield-halved text-xs"></i>
                                    <span>Desde Patrullaje {{ $preventivePatrolFolio }}</span>
                                </span>
                            @endif
                        </h3>
                    </div>
                    <button type="button" wire:click="closeModal" aria-label="Cerrar modal" class="text-brand-white/70 hover:text-brand-white focus:outline-none p-1">
                        <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i>
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

                                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">
                                    Tipo de Procedimiento (Al Llamar) *
                                </label>
                                <div class="mb-3">
                                    <x-searchable-select
                                        wire:model.live="procedure_type_id"
                                        :options="$procedureTypes"
                                        placeholder="Escriba o seleccione tipificación inicial..."
                                        searchPlaceholder="Digite parte del procedimiento (ej: ruidos, accidente, robo)..."
                                    />
                                </div>
                                @error('procedure_type_id') <span class="text-red-500 text-xs block mb-2">Seleccione un tipo.</span> @enderror

                                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">
                                    Origen de Ingreso *
                                </label>
                                <x-searchable-select
                                    wire:model="origin_id"
                                    :options="$origins"
                                    placeholder="Seleccione el origen..."
                                    searchPlaceholder="Buscar origen (ej: 1426, cámara, whatsapp)..."
                                />
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
                        @php
                            $hasLandmark = ! empty($landmark_id);
                            $hasManualAddress = ! $hasLandmark && (trim((string) $exact_address) !== '' || trim((string) $address_reference) !== '');
                        @endphp
                        <div class="lg:col-span-4 bg-slate-50 p-4 rounded-xl border border-slate-200"
                             x-data="{
                                 map: null,
                                 marker: null,
                                 geocoder: null,
                                 isGeocoding: false,
                                 skipNextGeocode: false,
                                 buinCenter: { lat: -33.7320, lng: -70.7420 },

                                 initLocationTools() {
                                     if (typeof google === 'undefined' || !google.maps) return;

                                     // 1. Inicializar Mini Mapa
                                     const mapEl = this.$refs.miniMapCanvas;
                                     if (mapEl && !this.map) {
                                         const initLat = parseFloat($wire.latitude);
                                         const initLng = parseFloat($wire.longitude);
                                         const hasInitialCoords = !isNaN(initLat) && !isNaN(initLng);
                                         const startCenter = hasInitialCoords ? { lat: initLat, lng: initLng } : this.buinCenter;

                                         this.map = new google.maps.Map(mapEl, {
                                             zoom: hasInitialCoords ? 17 : 14,
                                             center: startCenter,
                                             mapTypeControl: false,
                                             streetViewControl: false,
                                             fullscreenControl: false,
                                             zoomControl: true,
                                         });

                                         this.marker = new google.maps.Marker({
                                             map: this.map,
                                             position: startCenter,
                                             draggable: true,
                                             visible: hasInitialCoords,
                                             title: 'Arrastre para afinar la ubicación exacta del procedimiento',
                                         });

                                         if (google.maps.Geocoder) {
                                             this.geocoder = new google.maps.Geocoder();
                                         }

                                         // Clic en el mini mapa para fijar/afinar el punto
                                         this.map.addListener('click', (e) => {
                                             this.placeMarkerAndSync(e.latLng.lat(), e.latLng.lng(), false);
                                         });

                                         // Arrastrar marcador para afinar ubicación
                                         this.marker.addListener('dragend', (e) => {
                                             this.placeMarkerAndSync(e.latLng.lat(), e.latLng.lng(), false);
                                         });
                                     }

                                     // 2. Inicializar Autocomplete de Google Places
                                     const input = this.$refs.searchInput;
                                     if (input && google.maps.places && !input.dataset.autocompleteBound) {
                                         input.dataset.autocompleteBound = 'true';
                                         const options = {
                                             componentRestrictions: { country: 'cl' },
                                             fields: ['geometry', 'formatted_address', 'name'],
                                         };
                                         const autocomplete = new google.maps.places.Autocomplete(input, options);
                                         autocomplete.addListener('place_changed', () => {
                                             if ($wire.landmark_id) return;
                                             const place = autocomplete.getPlace();
                                             if (!place.geometry || !place.geometry.location) return;
                                             const lat = place.geometry.location.lat();
                                             const lng = place.geometry.location.lng();
                                             this.skipNextGeocode = true;
                                             this.placeMarkerAndSync(lat, lng, true);
                                             $wire.set('formatted_address', place.formatted_address || '');
                                             $wire.set('exact_address', place.name ? (place.name + ', ' + (place.formatted_address || '')) : (place.formatted_address || ''));
                                         });
                                     }
                                 },

                                 placeMarkerAndSync(lat, lng, panAndZoom = true) {
                                     const numLat = parseFloat(lat);
                                     const numLng = parseFloat(lng);
                                     if (isNaN(numLat) || isNaN(numLng)) return;

                                     if (this.map && this.marker) {
                                         const pos = { lat: numLat, lng: numLng };
                                         this.marker.setPosition(pos);
                                         this.marker.setVisible(true);
                                         if (panAndZoom) {
                                             this.map.panTo(pos);
                                             this.map.setZoom(17);
                                         }
                                     }
                                     $wire.setCoordinates(numLat, numLng);
                                 },

                                 moveToCoordinates(lat, lng) {
                                     const numLat = parseFloat(lat);
                                     const numLng = parseFloat(lng);
                                     if (isNaN(numLat) || isNaN(numLng) || !this.map || !this.marker) return;
                                     const pos = { lat: numLat, lng: numLng };
                                     this.marker.setPosition(pos);
                                     this.marker.setVisible(true);
                                     this.map.panTo(pos);
                                     this.map.setZoom(17);
                                 },

                                 resetMapToBuin() {
                                     if (this.$refs.searchInput) {
                                         this.$refs.searchInput.value = '';
                                     }
                                     if (this.map && this.marker) {
                                         this.marker.setVisible(false);
                                         this.map.panTo(this.buinCenter);
                                         this.map.setZoom(14);
                                     }
                                 },

                                 geocodeText(rawAddress) {
                                     if (this.skipNextGeocode) {
                                         this.skipNextGeocode = false;
                                         return;
                                     }
                                     const query = String(rawAddress || '').trim();
                                     if (query.length < 3) return;

                                     const searchQuery = query.toLowerCase().includes('buin')
                                         ? query + ', Chile'
                                         : query + ', Buin, Región Metropolitana, Chile';

                                     this.isGeocoding = true;

                                     const fallbackNominatim = () => {
                                         fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=cl&q=' + encodeURIComponent(searchQuery))
                                             .then(r => r.json())
                                             .then(results => {
                                                 this.isGeocoding = false;
                                                 if (Array.isArray(results) && results.length > 0) {
                                                     this.placeMarkerAndSync(parseFloat(results[0].lat), parseFloat(results[0].lon), true);
                                                 }
                                             })
                                             .catch(() => {
                                                 this.isGeocoding = false;
                                             });
                                     };

                                     if (this.geocoder) {
                                         this.geocoder.geocode({ address: searchQuery, region: 'CL' }, (results, status) => {
                                             if (status === 'OK' && results && results[0] && results[0].geometry) {
                                                 this.isGeocoding = false;
                                                 const loc = results[0].geometry.location;
                                                 this.placeMarkerAndSync(loc.lat(), loc.lng(), true);
                                             } else {
                                                 fallbackNominatim();
                                             }
                                         });
                                     } else {
                                         fallbackNominatim();
                                     }
                                 }
                             }"
                             x-init="$nextTick(() => initLocationTools())"
                             @location-updated.window="if ($refs.searchInput) { $refs.searchInput.value = ''; } moveToCoordinates($event.detail.lat, $event.detail.lng)"
                             @location-cleared.window="resetMapToBuin()"
                             @geocode-address.window="geocodeText($event.detail.address)">

                            <div class="flex flex-wrap items-center justify-between gap-2 mb-3 border-b border-slate-200 pb-1.5">
                                <h4 class="font-black text-slate-800 text-sm">
                                    📍 2. Ubicación Georreferenciada
                                </h4>
                                <span class="text-[11px] text-slate-500 font-semibold">
                                    Seleccione un <strong>Hito Rápido</strong> (sin dirección clara) o ingrese la <strong>Dirección Exacta</strong>
                                </span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                                <div>
                                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">
                                        Sector Jurisdiccional *
                                    </label>
                                    <x-searchable-select
                                        wire:model="sector_id"
                                        :options="$sectors"
                                        placeholder="Seleccione un sector..."
                                        searchPlaceholder="Digite sector (ej: Maipo, Viluco, Centro)..."
                                    />
                                    @error('sector_id') <span class="text-red-500 text-xs block mt-1">Seleccione el sector.</span> @enderror
                                </div>

                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-xs font-bold uppercase text-slate-600">
                                            Punto de Interés (Hito Rápido)
                                        </label>
                                        @if($hasManualAddress)
                                            <span class="text-[10px] font-bold text-amber-700 bg-amber-100 border border-amber-200 px-1.5 py-0.5 rounded">
                                                🔒 Deshabilitado por dirección
                                            </span>
                                        @elseif($hasLandmark)
                                            <button type="button"
                                                    wire:click="clearLocationSelection"
                                                    class="text-[10px] font-bold text-red-600 hover:text-red-800 hover:underline">
                                                ✕ Quitar hito
                                            </button>
                                        @endif
                                    </div>
                                    <x-searchable-select
                                        wire:key="landmark-select-{{ $hasManualAddress ? 'locked' : 'free' }}"
                                        wire:model.live="landmark_id"
                                        :options="$landmarks"
                                        :disabled="$hasManualAddress"
                                        placeholder="{{ $hasManualAddress ? 'Deshabilitado al usar dirección exacta...' : 'Seleccione hito de referencia...' }}"
                                        searchPlaceholder="Buscar hito o punto de interés..."
                                    />
                                </div>
                            </div>

                            {{-- Bloque de Campos de Dirección Exacta (se bloquea cuando se elige un Hito) --}}
                            <div class="space-y-3 p-3 rounded-xl border transition {{ $hasLandmark ? 'bg-slate-100/90 border-slate-200 opacity-75' : 'bg-white/70 border-slate-200/80' }}">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-extrabold uppercase tracking-wider {{ $hasLandmark ? 'text-amber-700' : 'text-slate-600' }}">
                                        @if($hasLandmark)
                                            <i class="fa-solid fa-lock mr-1"></i> Campos bloqueados: predomina la dirección del Hito seleccionado
                                        @else
                                            <i class="fa-solid fa-map-pin mr-1 text-blue-600"></i> Ingreso por Dirección Exacta
                                        @endif
                                    </span>

                                    @if($hasManualAddress)
                                        <button type="button"
                                                wire:click="clearLocationSelection"
                                                class="text-[11px] font-bold text-slate-500 hover:text-red-600 transition flex items-center gap-1">
                                            <i class="fa-solid fa-eraser text-[10px]"></i>
                                            <span>Limpiar dirección</span>
                                        </button>
                                    @endif
                                </div>

                                <!-- Buscador Google Maps -->
                                <div>
                                    <label class="block text-xs font-bold uppercase text-slate-600">
                                        Buscador de Calle / Intersección (Google Maps)
                                    </label>
                                    <div wire:ignore class="mt-1">
                                        <input x-ref="searchInput"
                                            type="text"
                                            :disabled="$wire.landmark_id !== '' && $wire.landmark_id !== null"
                                            :class="($wire.landmark_id !== '' && $wire.landmark_id !== null) ? 'bg-slate-100 text-slate-400 cursor-not-allowed' : 'bg-white'"
                                            class="block w-full rounded-lg border-slate-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm transition"
                                            placeholder="Escribe calle, numeración o intersección en Buin...">
                                    </div>
                                </div>

                                <!-- Dirección Exacta y Referencia -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-bold uppercase text-slate-600 mb-1">
                                            Dirección Exacta Confirmada *
                                        </label>
                                        <div class="flex gap-1.5">
                                            <input type="text"
                                                wire:model.live.debounce.500ms="exact_address"
                                                @disabled($hasLandmark)
                                                @readonly($hasLandmark)
                                                placeholder="Se autocompleta o escriba calle y número..."
                                                class="block w-full rounded-lg border-slate-300 shadow-sm text-sm font-medium {{ $hasLandmark ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'bg-white' }}">
                                            @if(!$hasLandmark)
                                                <button type="button"
                                                        @click="geocodeText($wire.exact_address || $refs.searchInput?.value)"
                                                        title="Ubicar dirección en el mini mapa"
                                                        class="shrink-0 px-2.5 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-xs transition flex items-center gap-1">
                                                    <i class="fa-solid fa-magnifying-glass-location" x-show="!isGeocoding"></i>
                                                    <i class="fa-solid fa-circle-notch fa-spin" x-show="isGeocoding" style="display: none;"></i>
                                                </button>
                                            @endif
                                        </div>
                                        @error('exact_address')
                                            <span class="text-red-500 text-xs block mt-1">
                                                La dirección es obligatoria.
                                            </span>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold uppercase text-slate-600 mb-1">
                                            Conjunto Habitacional / Villa / Referencia
                                        </label>
                                        <x-searchable-select
                                            wire:key="address-ref-select-{{ $hasLandmark ? 'locked' : 'free' }}"
                                            wire:model.live="address_reference"
                                            :options="$housingComplexes ?? []"
                                            :allowCustom="true"
                                            :disabled="$hasLandmark"
                                            placeholder="{{ $hasLandmark ? 'Bloqueado por selección de hito...' : 'Buscar Villa / Conjunto o escribir referencia...' }}"
                                            searchPlaceholder="Digite nombre de Villa, Condominio o referencia libre..."
                                        />
                                    </div>
                                </div>
                            </div>

                            {{-- Mini Mapa Interactivo para afinar punto del procedimiento --}}
                            <div class="mt-3 bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
                                <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                                    <div>
                                        <span class="text-xs font-black text-slate-800 flex items-center gap-1.5">
                                            <i class="fa-solid fa-map-location-dot text-blue-600"></i>
                                            <span>Mini Mapa de Georreferenciación (Referencia para Inspector)</span>
                                        </span>
                                        <p class="text-[11px] text-slate-500">
                                            Haga clic en el mapa o arrastre el marcador para afinar el punto exacto donde debe acudir el móvil.
                                        </p>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        @if($latitude && $longitude)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-mono font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                <i class="fa-solid fa-location-crosshairs text-emerald-600"></i>
                                                {{ number_format((float) $latitude, 5) }}, {{ number_format((float) $longitude, 5) }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-500 border border-slate-200">
                                                Sin coordenadas fijadas
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <div wire:ignore class="w-full h-48 rounded-lg border border-slate-300 overflow-hidden shadow-inner relative bg-slate-100">
                                    <div x-ref="miniMapCanvas" class="w-full h-full"></div>
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
                                            {{ $callerStats['total_calls'] }} llamadas registradas
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
                        <div class="{{ $is_preventive ? 'lg:col-span-6' : 'lg:col-span-2' }} bg-ui-subtle p-4 rounded-xl border border-ui-border">
                            <h4 class="font-black text-ui-fg text-sm mb-3 border-b border-ui-border pb-1.5 flex items-center gap-1.5">
                                <i class="fa-solid fa-car-on text-brand-cobalt dark:text-brand-cobalt-mid" aria-hidden="true"></i>
                                <span>4. Asignar Unidad</span>
                            </h4>

                            <label class="block text-xs font-bold uppercase text-ui-muted mb-1">
                                Despachar Móvil (Opcional)
                            </label>
                            @php
                                $deviceOptions = $devices->map(fn($d) => [
                                    'value' => $d->id,
                                    'label' => $d->name,
                                    'meta'  => $d->effective_status . ($d->active_procedures_count > 0 ? ' · ' . $d->active_procedures_count . ' caso(s)' : ''),
                                ])->values()->toArray();
                            @endphp
                            <x-searchable-select
                                wire:model="device_id"
                                :options="$deviceOptions"
                                placeholder="⏳ Sin asignar (Dejar Pendiente en Sala)"
                                searchPlaceholder="Buscar móvil o estado (ej: Móvil 15, Moto, Disponible)..."
                            />

                            @if($is_preventive)
                                <div class="mt-3">
                                    <label class="block text-xs font-bold uppercase text-ui-muted">
                                        Detalle del Patrullaje Preventivo
                                    </label>
                                    <textarea wire:model="initial_description" rows="2" placeholder="Indique objetivo o punto focal del patrullaje preventivo..." class="mt-1 block w-full rounded-lg border-ui-border shadow-xs text-sm"></textarea>
                                </div>
                            @endif
                        </div>

                    </div>

                    <!-- Botones -->
                    <div class="flex justify-end space-x-3 border-t border-ui-border pt-4">
                        <x-cad.button type="button" variant="secondary" size="lg" wire:click="closeModal">
                            Cancelar
                        </x-cad.button>

                        <x-cad.button type="submit" variant="primary" size="lg">
                            <i class="fa-solid fa-check" aria-hidden="true"></i>
                            <span>Registrar Procedimiento</span>
                        </x-cad.button>
                    </div>

                </form>
            </div>
        </div>
    @endif
    
</div>