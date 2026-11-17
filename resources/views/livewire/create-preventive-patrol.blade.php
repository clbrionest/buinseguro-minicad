<div>
    {{-- Botón opcional si se invoca con showButton=true --}}
    @if($showButton)
        <button type="button"
                wire:click="openModal"
                class="bg-brand-cobalt hover:bg-brand-cobalt-deep text-brand-white font-extrabold py-2 px-4 rounded-lg shadow-xs text-xs inline-flex items-center gap-1.5 transition min-h-[38px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ui-focus">
            <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
            <span>Ingresar Patrullaje Preventivo</span>
        </button>
    @endif

    {{-- MODAL DE INGRESO DE PATRULLAJE PREVENTIVO --}}
    @if($isOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/80 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4"
             x-data
             @keydown.escape.window="$wire.closeModal()">
            
            <div class="bg-ui-card rounded-2xl shadow-2xl border border-ui-border w-full max-w-4xl overflow-hidden transition-all transform scale-100 my-8">
                
                {{-- Encabezado del Modal --}}
                <div class="flex justify-between items-center bg-ui-sidebar text-brand-white px-6 py-4 rounded-t-2xl border-b border-white/10">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-brand-lime text-brand-black flex items-center justify-center text-sm font-black shadow-xs">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                        <div>
                            <span class="text-[10px] font-extrabold uppercase tracking-widest text-brand-lime-light block">OPERACIÓN DE FLOTA Y PREVENCIÓN</span>
                            <h3 class="text-lg font-extrabold text-white">Ingreso de Patrullaje Preventivo</h3>
                        </div>
                    </div>
                    <button type="button"
                            wire:click="closeModal"
                            aria-label="Cerrar modal"
                            class="text-white/70 hover:text-white transition p-1.5 rounded-lg hover:bg-white/10">
                        <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i>
                    </button>
                </div>

                {{-- Formulario --}}
                <form wire:submit.prevent="save" class="p-6 space-y-5">
                    
                    {{-- Bloque 1: Asignación de Unidad y Sector --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-ui-subtle p-4 rounded-xl border border-ui-border">
                        {{-- Móvil / Vehículo --}}
                        <div>
                            <label class="block text-xs font-black uppercase text-ui-fg mb-1">
                                Móvil / Vehículo *
                            </label>
                            <select wire:model.live="device_id" class="w-full text-xs font-bold rounded-lg border-ui-border bg-ui-card text-ui-fg focus:ring-brand-cobalt">
                                <option value="">-- Seleccione Móvil --</option>
                                @foreach($devices as $dev)
                                    <option value="{{ $dev->id }}">
                                        {{ $dev->name }} ({{ $dev->effective_status }})
                                        @if($dev->activePatrolAssignment?->sector)
                                            - Sector: {{ $dev->activePatrolAssignment->sector->name }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('device_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        {{-- Sector --}}
                        <div>
                            <label class="block text-xs font-black uppercase text-ui-fg mb-1">
                                Sector Asignado *
                            </label>
                            <select wire:model="sector_id" class="w-full text-xs font-bold rounded-lg border-ui-border bg-ui-card text-ui-fg focus:ring-brand-cobalt">
                                <option value="">-- Seleccione Sector --</option>
                                @foreach($sectors as $sec)
                                    <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                                @endforeach
                            </select>
                            @error('sector_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        {{-- Tipo de Patrullaje --}}
                        <div>
                            <label class="block text-xs font-black uppercase text-ui-fg mb-1">
                                Tipo de Patrullaje *
                            </label>
                            <select wire:model="patrol_type" class="w-full text-xs font-bold rounded-lg border-ui-border bg-ui-card text-ui-fg focus:ring-brand-cobalt">
                                @foreach($patrolTypes as $type)
                                    <option value="{{ $type }}">{{ $type }}</option>
                                @endforeach
                            </select>
                            @error('patrol_type') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Bloque 2: Tiempos y Consigna --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-black uppercase text-ui-fg mb-1">
                                Fecha y Hora de Inicio *
                            </label>
                            <input type="datetime-local"
                                   wire:model="started_at"
                                   class="w-full text-xs rounded-lg border-ui-border bg-ui-card text-ui-fg font-mono focus:ring-brand-cobalt">
                            @error('started_at') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-black uppercase text-ui-fg mb-1">
                                Odómetro Inicial (Km)
                            </label>
                            <input type="number"
                                   wire:model="start_odometer"
                                   placeholder="Ej: 145200"
                                   class="w-full text-xs rounded-lg border-ui-border bg-ui-card text-ui-fg font-mono focus:ring-brand-cobalt">
                            @error('start_odometer') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="md:col-span-1">
                            <label class="block text-xs font-black uppercase text-ui-fg mb-1">
                                Misión / Consigna del Recorrido
                            </label>
                            <input type="text"
                                   wire:model="mission_objective"
                                   placeholder="Ej: Ronda preventiva en paraderos y colegios..."
                                   class="w-full text-xs rounded-lg border-ui-border bg-ui-card text-ui-fg focus:ring-brand-cobalt">
                        </div>
                    </div>

                    {{-- Bloque 3: Asignación de Hitos y Puntos de Control --}}
                    <div class="bg-ui-subtle p-4 rounded-xl border border-ui-border space-y-3">
                        <div class="flex items-center justify-between flex-wrap gap-2 border-b border-ui-border pb-2">
                            <div>
                                <h4 class="text-xs font-black uppercase tracking-wider text-ui-fg flex items-center gap-1.5">
                                    <span>📍</span> Hitos y Puntos de Control Asignados
                                </h4>
                                <p class="text-[11px] text-ui-muted">
                                    Seleccione los puntos clave de Buin que el móvil deberá validar durante su recorrido preventivo.
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button"
                                        wire:click="selectAllLandmarks"
                                        class="text-[11px] font-bold text-brand-cobalt dark:text-brand-cobalt-light hover:underline">
                                    Seleccionar Todos
                                </button>
                                <span class="text-ui-muted">|</span>
                                <button type="button"
                                        wire:click="clearSelectedLandmarks"
                                        class="text-[11px] font-bold text-red-500 hover:underline">
                                    Limpiar Selección
                                </button>
                            </div>
                        </div>

                        {{-- Lista de Hitos / Landmarks --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 max-h-52 overflow-y-auto pr-1">
                            @forelse($landmarks as $lm)
                                <label class="flex items-start gap-2 p-2 rounded-lg bg-ui-card border border-ui-border hover:border-brand-cobalt/40 cursor-pointer text-xs transition">
                                    <input type="checkbox"
                                           wire:model="selected_landmarks"
                                           value="{{ $lm->id }}"
                                           class="mt-0.5 rounded border-ui-border text-brand-cobalt focus:ring-brand-cobalt">
                                    <div class="min-w-0 flex-1">
                                        <div class="font-bold text-ui-fg truncate">{{ $lm->name }}</div>
                                        <div class="text-[10px] text-ui-muted truncate">{{ $lm->address }}</div>
                                    </div>
                                </label>
                            @empty
                                <div class="col-span-full text-center py-4 text-xs text-ui-muted">
                                    No hay hitos registrados en el mantenedor. Puede agregar puntos personalizados a continuación.
                                </div>
                            @endforelse
                        </div>

                        {{-- Agregar Punto Personalizado Ad-Hoc --}}
                        <div class="pt-3 border-t border-ui-border">
                            <span class="text-[11px] font-bold text-ui-fg block mb-2">
                                + Agregar Punto de Control Ad-hoc / Específico:
                            </span>
                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-center">
                                <div class="sm:col-span-6">
                                    <input type="text"
                                           wire:model="new_checkpoint_name"
                                           placeholder="Nombre del punto (ej: Plaza Los Rosales)"
                                           class="w-full text-xs rounded-lg border-ui-border bg-ui-card text-ui-fg">
                                </div>
                                <div class="sm:col-span-4">
                                    <input type="text"
                                           wire:model="new_checkpoint_address"
                                           placeholder="Dirección o referencia"
                                           class="w-full text-xs rounded-lg border-ui-border bg-ui-card text-ui-fg">
                                </div>
                                <div class="sm:col-span-2">
                                    <button type="button"
                                            wire:click="addCustomCheckpoint"
                                            class="w-full bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold py-2 px-3 rounded-lg transition">
                                        Agregar
                                    </button>
                                </div>
                            </div>
                            @error('new_checkpoint_name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror

                            {{-- Chips de hitos personalizados agregados --}}
                            @if(count($custom_checkpoints) > 0)
                                <div class="flex flex-wrap gap-2 mt-2">
                                    @foreach($custom_checkpoints as $idx => $cp)
                                        <span class="inline-flex items-center gap-1.5 text-xs bg-brand-cobalt/15 text-brand-cobalt dark:text-brand-cobalt-light px-2.5 py-1 rounded-full border border-brand-cobalt/30">
                                            <span>📍 {{ $cp['name'] }}</span>
                                            <button type="button"
                                                    wire:click="removeCustomCheckpoint({{ $idx }})"
                                                    class="hover:text-red-500 font-bold ml-1">✕</button>
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Botonera de Acción --}}
                    <div class="flex items-center justify-between border-t border-ui-border pt-4">
                        <button type="button"
                                wire:click="closeModal"
                                class="px-4 py-2 text-xs font-bold rounded-lg border border-ui-border text-ui-fg hover:bg-ui-subtle transition">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-6 py-2 text-xs font-black rounded-lg bg-brand-cobalt hover:bg-brand-cobalt-deep text-brand-white transition shadow-sm flex items-center gap-2">
                            <i class="fa-solid fa-play text-xs"></i>
                            <span>Iniciar Patrullaje Preventivo</span>
                        </button>
                    </div>

                </form>

            </div>

        </div>
    @endif
</div>
