<div class="py-6 max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8" wire:poll.10s>
    
    {{-- CABECERA Y SELECTOR DE EVENTO MASIVO --}}
    <div class="bg-slate-900 text-white rounded-xl shadow-lg p-5 mb-6 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-2xl">🌩️</span>
                <h2 class="text-2xl font-black tracking-wide">CENTRO DE CONTINGENCIAS Y EVENTOS MASIVOS</h2>
            </div>
            <p class="text-sm text-slate-300 mt-1">
                Recolector rápido de reportes vecinales (frentes de mal tiempo, cortes de suministro, emergencias comunales) y asignación de móviles.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 w-full lg:w-auto">
            @role('Administrador')
                <a href="{{ route('catalogs.manage') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-600 px-3 py-2 rounded-lg text-xs font-bold transition">
                    ⚙️ Mantenedor Categorías
                </a>
            @endrole

            @if($events->count() > 0)
                <select wire:model.live="selectedEventId" class="bg-slate-800 text-white border border-slate-600 rounded-lg px-3 py-2 text-sm font-bold focus:ring-amber-500">
                    @foreach($events as $ev)
                        <option value="{{ $ev->id }}">
                            {{ $ev->is_active ? '🟢 [ACTIVO] ' : '⚪ [CERRADO] ' }} {{ $ev->name }} ({{ $ev->created_at->format('d/m/Y') }})
                        </option>
                    @endforeach
                </select>
            @endif

            @hasanyrole('Administrador|Supervisor')
                @if($currentEvent)
                    <button wire:click="toggleEventStatus({{ $currentEvent->id }})" class="px-3 py-2 rounded-lg text-xs font-bold border transition {{ $currentEvent->is_active ? 'bg-red-600 hover:bg-red-700 border-red-500 text-white' : 'bg-emerald-600 hover:bg-emerald-700 border-emerald-500 text-white' }}">
                        {{ $currentEvent->is_active ? '⏹ Finalizar Evento' : '▶ Reactivar Evento' }}
                    </button>
                @endif
                <button wire:click="$set('isNewEventModalOpen', true)" class="bg-amber-500 hover:bg-amber-600 text-slate-900 font-black px-4 py-2 rounded-lg shadow text-sm transition">
                    + Activar Nuevo Evento
                </button>
            @endhasanyrole
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-800 p-4 mb-6 rounded shadow-sm font-bold">
            {{ session('message') }}
        </div>
    @endif

    @if(!$currentEvent)
        <div class="bg-white rounded-lg shadow border p-12 text-center">
            <div class="text-5xl mb-3">🌤️</div>
            <h3 class="text-xl font-bold text-gray-800 mb-1">No hay eventos masivos registrados</h3>
            <p class="text-sm text-gray-500 mb-4">Cuando ocurra un frente de mal tiempo o emergencia comunal, actívalo aquí para recolectar reportes vecinales rápidamente.</p>
            @hasanyrole('Administrador|Supervisor')
                <button wire:click="$set('isNewEventModalOpen', true)" class="bg-amber-500 hover:bg-amber-600 text-gray-900 font-black px-5 py-2.5 rounded-lg shadow">
                    + Crear Primer Evento Masivo
                </button>
            @endhasanyrole
        </div>
    @else
        {{-- RESUMEN EJECUTIVO DE ESTADOS Y CATEGORÍAS (CLICABLES PARA FILTRAR) --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
            <button wire:click="$set('filterStatus', '')" class="text-left bg-white p-4 rounded-lg shadow-sm border-l-4 border-gray-800 {{ $filterStatus === '' ? 'ring-2 ring-gray-800' : '' }}">
                <p class="text-xs font-bold text-gray-500 uppercase">Total Reportes Evento</p>
                <p class="text-3xl font-black text-gray-900">{{ $statusCounts['total'] }}</p>
            </button>
            <button wire:click="$set('filterStatus', 'Pendiente')" class="text-left bg-white p-4 rounded-lg shadow-sm border-l-4 border-red-500 {{ $filterStatus === 'Pendiente' ? 'ring-2 ring-red-500 bg-red-50' : '' }}">
                <p class="text-xs font-bold text-red-600 uppercase">Pendientes de Móvil</p>
                <p class="text-3xl font-black text-red-600">{{ $statusCounts['Pendiente'] }}</p>
            </button>
            <button wire:click="$set('filterStatus', 'En Atención')" class="text-left bg-white p-4 rounded-lg shadow-sm border-l-4 border-amber-500 {{ $filterStatus === 'En Atención' ? 'ring-2 ring-amber-500 bg-amber-50' : '' }}">
                <p class="text-xs font-bold text-amber-600 uppercase">En Atención (Con Móvil)</p>
                <p class="text-3xl font-black text-amber-600">{{ $statusCounts['En Atención'] }}</p>
            </button>
            <button wire:click="$set('filterStatus', 'Resuelto')" class="text-left bg-white p-4 rounded-lg shadow-sm border-l-4 border-green-500 {{ $filterStatus === 'Resuelto' ? 'ring-2 ring-green-500 bg-green-50' : '' }}">
                <p class="text-xs font-bold text-green-600 uppercase">Puntos Resueltos</p>
                <p class="text-3xl font-black text-green-600">{{ $statusCounts['Resuelto'] }}</p>
            </button>
        </div>

        {{-- TARJETAS DE CONTEO RÁPIDO POR CATEGORÍA --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2.5 mb-6">
            @foreach($categories as $catName => $meta)
                <button type="button"
                        wire:click="toggleCategoryFilter('{{ $catName }}')"
                        class="p-2.5 rounded-lg border text-left transition flex flex-col justify-between {{ $filterCategory === $catName ? 'ring-2 ring-blue-600 bg-blue-50 border-blue-400' : 'bg-white hover:bg-gray-50 border-gray-200' }}">
                    <div class="flex items-center justify-between">
                        <span class="text-xl">{{ $meta['icon'] }}</span>
                        <span class="text-lg font-black text-gray-800">{{ $categoryCounts[$catName] ?? 0 }}</span>
                    </div>
                    <p class="text-[11px] font-bold text-gray-700 leading-tight mt-1.5">{{ $catName }}</p>
                </button>
            @endforeach
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            {{-- COLUMNA IZQUIERDA: RECOLECTOR RÁPIDO DE LLAMADOS VECINALES --}}
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md border-t-4 border-amber-500 p-5 sticky top-6">
                    <div class="flex justify-between items-center border-b pb-2 mb-4">
                        <h3 class="font-black text-lg text-gray-800">⚡ Ingreso Rápido de Reporte</h3>
                        <span class="text-xs font-bold px-2 py-0.5 rounded {{ $currentEvent->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-600' }}">
                            {{ $currentEvent->is_active ? 'Activo' : 'Solo Lectura' }}
                        </span>
                    </div>

                    @if(!$currentEvent->is_active)
                        <div class="p-4 bg-gray-100 rounded text-center text-sm text-gray-600 font-medium">
                            Este evento se encuentra cerrado. Reactívalo si necesitas ingresar nuevos reportes.
                        </div>
                    @else
                        <form wire:submit.prevent="saveQuickReport" class="space-y-3">
                            {{-- 1. Categoría rápida con botones --}}
                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-600 mb-1.5">1. Categoría del Problema</label>
                                <div class="grid grid-cols-2 gap-1.5">
                                    @foreach($categories as $catName => $meta)
                                        <button type="button"
                                                wire:click="$set('category', '{{ $catName }}')"
                                                class="text-left px-2.5 py-1.5 rounded border text-xs font-bold flex items-center gap-1.5 transition {{ $category === $catName ? 'bg-amber-500 text-gray-900 border-amber-600 shadow-sm' : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100' }}">
                                            <span>{{ $meta['icon'] }}</span>
                                            <span class="truncate">{{ $catName }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            {{-- 2. Urgencia y Sector --}}
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold uppercase text-gray-600 mb-1">2. Urgencia</label>
                                    <select wire:model="urgency" class="w-full rounded-md border-gray-300 text-sm font-bold">
                                        <option value="Alta">🔴 Alta / Peligro</option>
                                        <option value="Media">🟡 Media</option>
                                        <option value="Baja">🟢 Baja</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Sector</label>
                                    <select wire:model="sector_id" class="w-full rounded-md border-gray-300 text-sm">
                                        <option value="">Seleccionar...</option>
                                        @foreach($sectors as $sec)
                                            <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- 3. Dirección (Con soporte Google Places y texto directo rápido) --}}
                            <div x-data="{
                                    initPlaces() {
                                        if (typeof google === 'undefined' || !google.maps || !google.maps.places) return;
                                        const input = this.$refs.eventAddressInput;
                                        const autocomplete = new google.maps.places.Autocomplete(input, {
                                            componentRestrictions: { country: 'cl' },
                                            fields: ['geometry', 'formatted_address', 'name']
                                        });
                                        autocomplete.addListener('place_changed', () => {
                                            const place = autocomplete.getPlace();
                                            if (place.geometry && place.geometry.location) {
                                                $wire.set('latitude', place.geometry.location.lat());
                                                $wire.set('longitude', place.geometry.location.lng());
                                            }
                                            const addr = place.name ? (place.name + ', ' + (place.formatted_address || '')) : (place.formatted_address || input.value);
                                            $wire.set('exact_address', addr);
                                        });
                                    }
                                 }"
                                 x-init="initPlaces()"
                                 @report-saved.window="$refs.eventAddressInput.value = ''">
                                <label class="block text-xs font-bold uppercase text-gray-600 mb-1">3. Dirección o Intersección *</label>
                                <input x-ref="eventAddressInput"
                                       type="text"
                                       wire:model="exact_address"
                                       placeholder="Ej: Av. San Martín con Maipú, Buin"
                                       class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500">
                                @error('exact_address') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Referencia / Casa</label>
                                    <input type="text" wire:model="address_reference" placeholder="Ej: Frente a plaza" class="w-full rounded-md border-gray-300 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Teléfono Vecino</label>
                                    <input type="text" wire:model="caller_phone" placeholder="+569..." class="w-full rounded-md border-gray-300 text-sm">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Nombre Vecino</label>
                                    <input type="text" wire:model="caller_name" placeholder="Opcional" class="w-full rounded-md border-gray-300 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase text-blue-700 mb-1">🚗 Móvil que Asistirá</label>
                                    <select wire:model="device_id" class="w-full rounded-md border-blue-300 bg-blue-50 text-sm font-semibold">
                                        <option value="">En espera de móvil</option>
                                        @foreach($devices as $dev)
                                            <option value="{{ $dev->id }}">{{ $dev->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Detalle / Observación Rápida</label>
                                <textarea wire:model="notes" rows="2" placeholder="Ej: Agua entrando a vivienda, cables chispeando..." class="w-full rounded-md border-gray-300 text-sm"></textarea>
                            </div>

                            <button type="submit" class="w-full bg-gray-900 hover:bg-black text-white font-black py-2.5 px-4 rounded-lg shadow transition flex justify-center items-center gap-2">
                                <span>➕ Registrar Reporte en el Evento</span>
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- COLUMNA DERECHA: TABLA DE MONITOREO Y ASIGNACIÓN EN LÍNEA --}}
            <div class="lg:col-span-2 space-y-4">
                
                {{-- Barra de filtros rápidos --}}
                <div class="bg-white p-4 rounded-lg shadow-sm border flex flex-col sm:flex-row gap-3 items-center justify-between">
                    <div class="flex flex-wrap gap-2 items-center w-full sm:w-auto">
                        <select wire:model.live="filterSector" class="rounded-md border-gray-300 text-xs font-semibold">
                            <option value="">Todos los Sectores</option>
                            @foreach($sectors as $sec)
                                <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                            @endforeach
                        </select>

                        @if($filterCategory || $filterStatus || $filterSector || $search)
                            <button wire:click="$set('filterCategory', ''); $set('filterStatus', ''); $set('filterSector', ''); $set('search', '')"
                                    class="text-xs text-red-600 font-bold hover:underline px-2">
                                ✕ Limpiar filtros
                            </button>
                        @endif
                    </div>

                    <div class="w-full sm:w-64">
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar dirección, vecino, nota..." class="w-full rounded-md border-gray-300 text-xs">
                    </div>
                </div>

                {{-- Listado de Reportes del Evento --}}
                <div class="bg-white rounded-lg shadow-md border overflow-hidden">
                    <div class="bg-gray-800 text-white px-4 py-3 flex justify-between items-center">
                        <h4 class="font-bold text-sm uppercase tracking-wider">
                            📋 Reportes Recibidos — {{ $currentEvent->name }}
                        </h4>
                        <span class="text-xs bg-gray-700 px-2.5 py-1 rounded-full font-bold">
                            Mostrando {{ $reports->total() }} reportes
                        </span>
                    </div>

                    <div class="divide-y divide-gray-200">
                        @forelse($reports as $rep)
                            @php
                                $catMeta = $categories[$rep->category] ?? ['icon' => '📌', 'color' => 'bg-gray-100 text-gray-800 border-gray-300'];
                                $urgencyBadge = match($rep->urgency) {
                                    'Alta' => 'bg-red-100 text-red-800 border-red-300',
                                    'Media' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
                                    default => 'bg-green-100 text-green-800 border-green-300',
                                };
                            @endphp
                            <div class="p-4 hover:bg-gray-50 transition flex flex-col md:flex-row justify-between gap-4 {{ $rep->status === 'Resuelto' ? 'opacity-60 bg-gray-50' : '' }}">
                                <div class="flex-1">
                                    <div class="flex flex-wrap items-center gap-2 mb-1">
                                        <span class="text-xs font-bold px-2.5 py-0.5 rounded-full border {{ $catMeta['color'] }}">
                                            {{ $catMeta['icon'] }} {{ $rep->category }}
                                        </span>
                                        <span class="text-[11px] font-bold px-2 py-0.5 rounded border {{ $urgencyBadge }}">
                                            Urgencia {{ $rep->urgency }}
                                        </span>
                                        @if($rep->sector)
                                            <span class="text-xs font-semibold text-gray-600 bg-gray-100 px-2 py-0.5 rounded">
                                                📍 {{ $rep->sector->name }}
                                            </span>
                                        @endif
                                        <span class="text-xs text-gray-400 font-mono">
                                            #{{ $rep->id }} · {{ $rep->created_at->format('H:i') }}
                                        </span>
                                    </div>

                                    <p class="font-bold text-gray-900 text-sm">
                                        {{ $rep->exact_address }}
                                        @if($rep->address_reference)
                                            <span class="font-normal text-gray-500 text-xs">({{ $rep->address_reference }})</span>
                                        @endif
                                    </p>

                                    @if($rep->notes)
                                        <p class="text-xs text-gray-700 mt-1 bg-amber-50 border border-amber-100 p-1.5 rounded">
                                            💬 {{ $rep->notes }}
                                        </p>
                                    @endif

                                    <div class="text-[11px] text-gray-500 mt-1.5 flex flex-wrap gap-3">
                                        @if($rep->caller_name || $rep->caller_phone)
                                            <span>👤 <strong>Vecino:</strong> {{ $rep->caller_name ?: 'S/N' }} {{ $rep->caller_phone ? "({$rep->caller_phone})" : '' }}</span>
                                        @endif
                                        <span>🎧 <strong>Operador:</strong> {{ $rep->user->name ?? 'Sistema' }}</span>
                                    </div>
                                </div>

                                {{-- Controles rápidos de Despacho de Móvil y Estado --}}
                                <div class="flex flex-row md:flex-col justify-between items-end gap-2 min-w-[200px]">
                                    <div class="w-full">
                                        <label class="block text-[10px] font-bold uppercase text-gray-400 mb-0.5">Móvil Asignado</label>
                                        <select wire:change="assignDeviceToReport({{ $rep->id }}, $event.target.value)"
                                                class="w-full text-xs rounded border-gray-300 font-bold {{ $rep->device_id ? 'bg-blue-50 text-blue-800 border-blue-300' : 'text-red-600 bg-red-50 border-red-200' }}">
                                            <option value="">🚨 Sin Móvil</option>
                                            @foreach($devices as $dev)
                                                <option value="{{ $dev->id }}" @selected($rep->device_id == $dev->id)>
                                                    🚗 {{ $dev->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="flex items-center gap-1 w-full justify-end">
                                        <button type="button"
                                                wire:click="updateReportStatus({{ $rep->id }}, 'Pendiente')"
                                                class="px-2 py-1 rounded text-[11px] font-bold border {{ $rep->status === 'Pendiente' ? 'bg-red-600 text-white border-red-600' : 'bg-white text-gray-600 hover:bg-gray-100' }}">
                                            Pendiente
                                        </button>
                                        <button type="button"
                                                wire:click="updateReportStatus({{ $rep->id }}, 'En Atención')"
                                                class="px-2 py-1 rounded text-[11px] font-bold border {{ $rep->status === 'En Atención' ? 'bg-amber-500 text-white border-amber-500' : 'bg-white text-gray-600 hover:bg-gray-100' }}">
                                            En Atención
                                        </button>
                                        <button type="button"
                                                wire:click="updateReportStatus({{ $rep->id }}, 'Resuelto')"
                                                class="px-2 py-1 rounded text-[11px] font-bold border {{ $rep->status === 'Resuelto' ? 'bg-green-600 text-white border-green-600' : 'bg-white text-gray-600 hover:bg-gray-100' }}">
                                            ✓ Resuelto
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-10 text-center text-gray-400">
                                <p class="font-bold text-gray-500">No hay reportes vecinales con los filtros actuales.</p>
                                <p class="text-xs mt-1">Usa el formulario de la izquierda para recolectar llamados rápidamente.</p>
                            </div>
                        @endforelse
                    </div>

                    <div class="p-4 bg-gray-50 border-t">
                        {{ $reports->links() }}
                    </div>
                </div>

            </div>
        </div>
    @endif

    {{-- MODAL PARA CREAR NUEVO EVENTO MASIVO --}}
    @if($isNewEventModalOpen)
        <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full overflow-hidden">
                <div class="bg-gray-900 text-white px-5 py-4 flex justify-between items-center">
                    <h3 class="font-bold text-lg">🚨 Activar Evento Masivo / Contingencia</h3>
                    <button wire:click="$set('isNewEventModalOpen', false)" class="text-gray-400 hover:text-white">✕</button>
                </div>
                <form wire:submit.prevent="createEvent" class="p-5 space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Tipo de Contingencia</label>
                        <select wire:model="newEventType" class="w-full rounded-md border-gray-300 shadow-sm text-sm font-semibold">
                            <option value="Frente de Mal Tiempo">🌧️ Frente de Mal Tiempo / Temporal</option>
                            <option value="Corte Masivo de Suministro">⚡ Corte Masivo de Suministro</option>
                            <option value="Emergencia Mayor / Incendio">🔥 Emergencia Mayor / Incendio</option>
                            <option value="Evento Masivo Comunal">🎪 Evento Masivo Comunal / Fiesta</option>
                            <option value="Otra Contingencia">🚨 Otra Contingencia</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Nombre del Operativo / Evento *</label>
                        <input type="text" wire:model="newEventName" placeholder="Ej: Frente de Mal Tiempo Junio 2026" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                        @error('newEventName') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Instrucciones para Operadores (Opcional)</label>
                        <textarea wire:model="newEventDescription" rows="2" placeholder="Ej: Priorizar adultos mayores y anegamientos de viviendas..." class="w-full rounded-md border-gray-300 shadow-sm text-sm"></textarea>
                    </div>
                    <div class="flex justify-end gap-2 pt-3 border-t">
                        <button type="button" wire:click="$set('isNewEventModalOpen', false)" class="px-4 py-2 bg-gray-200 text-gray-700 rounded font-bold text-sm">Cancelar</button>
                        <button type="submit" class="px-5 py-2 bg-amber-500 hover:bg-amber-600 text-gray-900 rounded font-black text-sm shadow">
                            Activar Evento Ahora
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>
