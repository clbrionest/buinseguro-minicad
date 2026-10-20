<div class="py-6 max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">

    <div class="mb-5 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div>
            <h2 class="text-2xl font-black text-slate-800 tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-sliders text-blue-600"></i>
                <span>Mantenedores y Catálogos Operativos del Sistema</span>
            </h2>
            <p class="text-sm text-slate-500">
                Administre con validación completa todos los catálogos base, tipificaciones, metadatos, bloques horarios de turnos, puestos en sala, estados de flota y etiquetas del sistema.
            </p>
        </div>
    </div>

    {{-- SECCIÓN 1: PESTAÑAS DE CATÁLOGOS ESTRUCTURALES Y OPERATIVOS --}}
    <div class="bg-slate-900 rounded-t-xl shadow-sm p-3 space-y-2">
        <div class="text-[10px] font-black uppercase tracking-wider text-slate-400 px-1">
            <i class="fa-solid fa-database mr-1 text-blue-400"></i> Catálogos Base de Despacho
        </div>
        <div class="flex flex-wrap gap-1.5">
            <button wire:click="switchTab('sectors')" class="px-3.5 py-2 rounded-lg font-bold text-xs transition inline-flex items-center gap-1.5 {{ $activeTab === 'sectors' ? 'bg-blue-600 text-white shadow' : 'bg-slate-800 text-slate-300 hover:bg-slate-700 hover:text-white' }}">
                <i class="fa-solid fa-map-location-dot"></i> <span>Sectores / Jurisdicciones</span>
            </button>
            <button wire:click="switchTab('procedure_types')" class="px-3.5 py-2 rounded-lg font-bold text-xs transition inline-flex items-center gap-1.5 {{ $activeTab === 'procedure_types' ? 'bg-blue-600 text-white shadow' : 'bg-slate-800 text-slate-300 hover:bg-slate-700 hover:text-white' }}">
                <i class="fa-solid fa-clipboard-list"></i> <span>Tipos de Procedimiento (+ Metadatos)</span>
            </button>
            <button wire:click="switchTab('devices')" class="px-3.5 py-2 rounded-lg font-bold text-xs transition inline-flex items-center gap-1.5 {{ $activeTab === 'devices' ? 'bg-blue-600 text-white shadow' : 'bg-slate-800 text-slate-300 hover:bg-slate-700 hover:text-white' }}">
                <i class="fa-solid fa-car-on"></i> <span>Dispositivos / Móviles</span>
            </button>
            <button wire:click="switchTab('origins')" class="px-3.5 py-2 rounded-lg font-bold text-xs transition inline-flex items-center gap-1.5 {{ $activeTab === 'origins' ? 'bg-blue-600 text-white shadow' : 'bg-slate-800 text-slate-300 hover:bg-slate-700 hover:text-white' }}">
                <i class="fa-solid fa-phone-Arrow-down-left fa-phone"></i> <span>Orígenes de Ingreso</span>
            </button>
            <button wire:click="switchTab('event_categories')" class="px-3.5 py-2 rounded-lg font-bold text-xs transition inline-flex items-center gap-1.5 {{ $activeTab === 'event_categories' ? 'bg-amber-500 text-slate-950 shadow' : 'bg-slate-800 text-slate-300 hover:bg-slate-700 hover:text-white' }}">
                <i class="fa-solid fa-cloud-showers-heavy"></i> <span>Categorías Reportes Eventos</span>
            </button>
        </div>

        <div class="text-[10px] font-black uppercase tracking-wider text-slate-400 px-1 pt-2 border-t border-slate-800">
            <i class="fa-solid fa-tags mr-1 text-emerald-400"></i> Mantenedores de Turnos, Puestos, Etiquetas y Estados Operativos
        </div>
        <div class="flex flex-wrap gap-1.5">
            @foreach($systemGroups as $gKey => $gInfo)
                <button wire:click="switchTab('{{ $gKey }}')"
                        class="px-3 py-1.5 rounded-lg font-bold text-xs transition inline-flex items-center gap-1.5 {{ $activeTab === $gKey ? 'bg-emerald-600 text-white shadow' : 'bg-slate-800 text-slate-300 hover:bg-slate-700 hover:text-white' }}">
                    <i class="fa-solid fa-tag text-[10px]"></i>
                    <span>{{ $gInfo['label'] }}</span>
                </button>
            @endforeach
        </div>
    </div>

    <div class="bg-white shadow-md rounded-b-xl border border-t-0 border-slate-200 p-6">

        @if(isset($systemGroups[$activeTab]))
            <div class="mb-4 bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 flex items-center justify-between">
                <div class="text-xs text-slate-600">
                    <strong class="text-slate-900 uppercase">{{ $systemGroups[$activeTab]['label'] }}:</strong>
                    {{ $systemGroups[$activeTab]['description'] }}
                </div>
                <span class="text-[11px] font-mono bg-slate-200 text-slate-700 px-2 py-0.5 rounded font-bold">{{ $activeTab }}</span>
            </div>
        @endif

        @if (session()->has('message'))
            <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 p-3.5 mb-5 rounded font-semibold text-sm flex items-center gap-2" role="alert">
                <i class="fa-solid fa-circle-check text-emerald-600"></i>
                <span>{{ session('message') }}</span>
            </div>
        @endif
        @if (session()->has('error'))
            <div class="bg-red-50 border-l-4 border-red-500 text-red-800 p-3.5 mb-5 rounded font-semibold text-sm flex items-center gap-2" role="alert">
                <i class="fa-solid fa-triangle-exclamation text-red-600"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- FORMULARIO CREAR / EDITAR --}}
        <div class="bg-slate-50 p-5 rounded-xl border border-slate-200 mb-6">
            <div class="flex justify-between items-center mb-3">
                <h3 class="font-black text-slate-800 text-base flex items-center gap-2">
                    <i class="fa-solid {{ $editingId ? 'fa-pen-to-square text-amber-600' : 'fa-plus-circle text-blue-600' }}"></i>
                    <span>{{ $editingId ? 'Editando Registro #' . $editingId : 'Agregar Nuevo Registro al Catálogo' }}</span>
                </h3>
                @if($editingId)
                    <span class="text-xs font-bold bg-amber-100 text-amber-800 px-2.5 py-1 rounded-full">Modo Edición Activo</span>
                @endif
            </div>

            <form wire:submit.prevent="save" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-start">
                    <div class="{{ ($activeTab === 'event_categories' || isset($systemGroups[$activeTab])) ? 'md:col-span-5' : 'md:col-span-9' }}">
                        <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Nombre / Etiqueta del Registro *</label>
                        <input type="text" wire:model="name" class="w-full rounded-lg border-slate-300 shadow-sm text-sm font-medium focus:border-blue-500 focus:ring-blue-500" placeholder="Ingrese el nombre único...">
                        @error('name') <span class="text-red-600 text-xs font-semibold block mt-1">{{ $message }}</span> @enderror
                    </div>

                    @if($activeTab === 'event_categories')
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Icono / Símbolo</label>
                            <div class="flex gap-1">
                                <input type="text" wire:model="icon" class="w-16 text-center rounded-lg border-slate-300 shadow-sm text-base" maxlength="20">
                                <select wire:change="$set('icon', $event.target.value)" class="flex-1 rounded-lg border-slate-300 text-sm">
                                    <option value="">Elegir...</option>
                                    @foreach(['🌊','🌳','⚡','🏠','🚧','🛟','🔥','🚑','🚓','🪨','💧','❄️','📌'] as $em)
                                        <option value="{{ $em }}">{{ $em }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('icon') <span class="text-red-600 text-xs font-semibold block mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Color Etiqueta</label>
                            <select wire:model="color" class="w-full rounded-lg border-slate-300 shadow-sm text-sm font-bold">
                                <option value="blue">Azul</option>
                                <option value="green">Verde</option>
                                <option value="yellow">Amarillo</option>
                                <option value="orange">Naranjo</option>
                                <option value="red">Rojo</option>
                                <option value="purple">Morado</option>
                                <option value="gray">Gris</option>
                            </select>
                        </div>
                    @elseif(isset($systemGroups[$activeTab]))
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Icono FontAwesome</label>
                            <select wire:model="icon" class="w-full rounded-lg border-slate-300 shadow-sm text-xs font-semibold">
                                <option value="fa-tag">fa-tag (Etiqueta)</option>
                                <option value="fa-headset">fa-headset (Telefonía)</option>
                                <option value="fa-video">fa-video (Cámaras CCTV)</option>
                                <option value="fa-walkie-talkie">fa-walkie-talkie (Radio)</option>
                                <option value="fa-user-shield">fa-user-shield (Supervisor)</option>
                                <option value="fa-sun">fa-sun (Turno Día)</option>
                                <option value="fa-cloud-sun">fa-cloud-sun (Turno Tarde)</option>
                                <option value="fa-moon">fa-moon (Turno Noche)</option>
                                <option value="fa-car-on">fa-car-on (Patrulla)</option>
                                <option value="fa-circle-check">fa-circle-check (Disponible)</option>
                                <option value="fa-utensils">fa-utensils (Colación)</option>
                                <option value="fa-map-pin">fa-map-pin (Punto Fijo)</option>
                                <option value="fa-wrench">fa-wrench (Mantención)</option>
                                <option value="fa-triangle-exclamation">fa-triangle-exclamation (Alerta)</option>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Color Distintivo</label>
                            <select wire:model="color" class="w-full rounded-lg border-slate-300 shadow-sm text-xs font-bold">
                                <option value="blue">Azul Institucional</option>
                                <option value="green">Verde / Operativo</option>
                                <option value="yellow">Ámbar / Precaución</option>
                                <option value="red">Rojo / Crítico</option>
                                <option value="purple">Índigo / Cámaras</option>
                                <option value="gray">Gris / Neutro</option>
                            </select>
                        </div>

                        <div class="md:col-span-1">
                            <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Orden</label>
                            <input type="number" wire:model="sort_order" min="0" max="999" class="w-full rounded-lg border-slate-300 shadow-sm text-sm text-center font-bold">
                            @error('sort_order') <span class="text-red-600 text-[10px] block">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <div class="{{ ($activeTab === 'event_categories' || isset($systemGroups[$activeTab])) ? 'md:col-span-2' : 'md:col-span-3' }} flex items-end gap-2 pt-5">
                        <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-black py-2 px-4 rounded-lg shadow text-xs uppercase tracking-wider transition inline-flex items-center justify-center gap-1.5">
                            <i class="fa-solid {{ $editingId ? 'fa-floppy-disk' : 'fa-plus' }}"></i>
                            <span>{{ $editingId ? 'Actualizar' : 'Guardar' }}</span>
                        </button>
                        @if($editingId)
                            <button type="button" wire:click="resetForm" class="bg-slate-300 hover:bg-slate-400 text-slate-800 font-bold py-2 px-3 rounded-lg text-xs">
                                Cancelar
                            </button>
                        @endif
                    </div>
                </div>

                {{-- CONFIGURACIÓN DE DOTACIÓN Y HORARIO PARA BLOQUES DE TURNOS DE OPERADORES --}}
                @if($activeTab === 'operator_shift_blocks')
                    <div class="mt-3 pt-3 border-t border-slate-200 bg-blue-50/60 rounded-xl p-4 border border-blue-200">
                        <h4 class="text-xs font-black uppercase text-blue-900 mb-2 flex items-center gap-1.5">
                            <i class="fa-solid fa-users-viewfinder text-blue-600"></i>
                            Parámetros de Horario y Dotación Sugerida para este Bloque de Turno de Sala
                        </h4>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Hora Inicio (HH:MM) *</label>
                                <input type="time" wire:model="shift_start_time" class="w-full rounded-lg border-slate-300 text-sm font-mono font-bold">
                                @error('shift_start_time') <span class="text-red-600 text-[10px] font-bold">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Hora Término (HH:MM) *</label>
                                <input type="time" wire:model="shift_end_time" class="w-full rounded-lg border-slate-300 text-sm font-mono font-bold">
                                @error('shift_end_time') <span class="text-red-600 text-[10px] font-bold">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">
                                    <i class="fa-solid fa-headset text-blue-600 mr-1"></i> Operadores Telefónicos Requeridos
                                </label>
                                <input type="number" wire:model="min_phone_ops" min="0" max="30" class="w-full rounded-lg border-slate-300 text-sm font-bold">
                                @error('min_phone_ops') <span class="text-red-600 text-[10px] font-bold">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">
                                    <i class="fa-solid fa-video text-indigo-600 mr-1"></i> Operadores de Cámaras Requeridos
                                </label>
                                <input type="number" wire:model="min_camera_ops" min="0" max="50" class="w-full rounded-lg border-slate-300 text-sm font-bold">
                                @error('min_camera_ops') <span class="text-red-600 text-[10px] font-bold">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                @endif

                {{-- CONSTRUCTOR DE CAMPOS META DINÁMICOS PARA TIPOS DE PROCEDIMIENTO --}}
                @if($activeTab === 'procedure_types')
                    <div class="mt-4 pt-4 border-t border-slate-200 bg-white rounded-xl p-4 border shadow-inner">
                        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-3 mb-3">
                            <div>
                                <h4 class="text-sm font-black text-slate-800 flex items-center gap-1.5">
                                    <i class="fa-solid fa-puzzle-piece text-indigo-600"></i>
                                    Campos de Antecedentes / Metadatos para este Tipo de Procedimiento
                                </h4>
                                <p class="text-xs text-slate-500">
                                    Estos campos se solicitarán automáticamente al operador cuando ingrese o gestione un caso de esta categoría (ej: PPU vehículos, cant. heridos, fallecidos, especies, etc.).
                                </p>
                            </div>
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="text-[11px] font-bold text-slate-500 uppercase mr-1">Cargar Plantilla:</span>
                                @foreach($presets as $pKey => $pData)
                                    <button type="button"
                                            wire:click="applyMetaPreset('{{ $pKey }}')"
                                            class="text-[11px] bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 font-bold px-2.5 py-1 rounded-md transition">
                                        {{ $pData['name'] }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        @if(count($metaFields) > 0)
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2.5 mb-4">
                                @foreach($metaFields as $idx => $field)
                                    <div class="flex items-center justify-between bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs">
                                        <div class="truncate pr-2">
                                            <span class="font-black text-slate-800 block truncate">{{ $field['label'] }}</span>
                                            <span class="text-[10px] text-slate-500 uppercase font-semibold">
                                                Tipo: {{ $field['type'] }}
                                                @if(!empty($field['options'])) · Opciones: {{ Str::limit($field['options'], 25) }} @endif
                                            </span>
                                        </div>
                                        <button type="button" wire:click="removeMetaField({{ $idx }})" class="text-red-500 hover:text-red-700 font-black px-1.5 py-0.5 rounded hover:bg-red-50">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-xs text-slate-400 italic mb-3 bg-slate-50 p-2.5 rounded border border-dashed text-center">
                                Sin campos meta personalizados. Puedes agregar campos abajo o elegir una plantilla rápida arriba.
                            </div>
                        @endif

                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-end bg-slate-100 p-3 rounded-lg">
                            <div class="sm:col-span-4">
                                <label class="block text-[11px] font-bold text-slate-600 uppercase">Etiqueta del Campo (Ej: PPU Vehículos)</label>
                                <input type="text" wire:model="newFieldLabel" placeholder="Ej: Cantidad de Heridos" class="mt-0.5 w-full rounded border-slate-300 text-xs">
                                @error('newFieldLabel') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-[11px] font-bold text-slate-600 uppercase">Tipo de Campo</label>
                                <select wire:model.live="newFieldType" class="mt-0.5 w-full rounded border-slate-300 text-xs font-semibold">
                                    <option value="text">Texto / PPU</option>
                                    <option value="number">Número / Cantidad</option>
                                    <option value="select">Lista de Opciones</option>
                                    <option value="boolean">Sí / No</option>
                                </select>
                            </div>
                            <div class="sm:col-span-4">
                                @if($newFieldType === 'select')
                                    <label class="block text-[11px] font-bold text-indigo-700 uppercase">Opciones (Separadas por coma)</label>
                                    <input type="text" wire:model="newFieldOptions" placeholder="Ej: Leve, Grave, Fallecido" class="mt-0.5 w-full rounded border-indigo-300 text-xs">
                                @else
                                    <label class="block text-[11px] font-bold text-slate-600 uppercase">Texto de Ayuda (Placeholder)</label>
                                    <input type="text" wire:model="newFieldPlaceholder" placeholder="Ej: ABCD-12, JKLM-34" class="mt-0.5 w-full rounded border-slate-300 text-xs">
                                @endif
                            </div>
                            <div class="sm:col-span-2">
                                <button type="button" wire:click="addMetaField" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-bold py-2 px-3 rounded text-xs transition">
                                    + Añadir Campo
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            </form>
        </div>

        {{-- TABLA DE REGISTROS --}}
        <div class="overflow-x-auto rounded-lg border border-slate-200">
            <table class="min-w-full leading-normal">
                <thead>
                    <tr class="bg-slate-800 text-white text-xs uppercase tracking-wider font-bold">
                        <th class="py-3 px-5 text-left w-20">ID</th>
                        <th class="py-3 px-5 text-left">Nombre / Etiqueta</th>
                        @if($activeTab === 'event_categories' || isset($systemGroups[$activeTab]))
                            <th class="py-3 px-5 text-center">Vista Previa / Configuración</th>
                            <th class="py-3 px-5 text-center">Estado</th>
                        @elseif($activeTab === 'procedure_types')
                            <th class="py-3 px-5 text-left">Campos de Antecedentes (Metadatos)</th>
                        @endif
                        <th class="py-3 px-5 text-center w-48">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-slate-700 text-sm divide-y divide-slate-200">
                    @forelse ($items as $item)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3 px-5 font-mono text-xs font-bold text-slate-400">#{{ $item->id }}</td>
                            <td class="py-3 px-5 font-bold text-slate-800">{{ $item->name }}</td>

                            @if($activeTab === 'event_categories')
                                <td class="py-3 px-5 text-center">
                                    <span class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1 rounded-full border {{ $item->badge_classes }}">
                                        <span>{{ $item->icon }}</span>
                                        <span>{{ $item->name }}</span>
                                    </span>
                                </td>
                                <td class="py-3 px-5 text-center">
                                    <button wire:click="toggleItemActive({{ $item->id }})" class="text-xs font-bold px-2.5 py-0.5 rounded-full {{ ($item->is_active ?? true) ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">
                                        {{ ($item->is_active ?? true) ? 'Activo' : 'Inactivo' }}
                                    </button>
                                </td>
                            @elseif(isset($systemGroups[$activeTab]))
                                <td class="py-3 px-5 text-center">
                                    <div class="inline-flex flex-wrap items-center justify-center gap-2">
                                        <span class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1 rounded-full border {{ $item->badge_classes }}">
                                            @if($item->icon)
                                                <i class="fa-solid {{ $item->icon }}"></i>
                                            @endif
                                            <span>{{ $item->name }}</span>
                                        </span>
                                        @if($activeTab === 'operator_shift_blocks' && is_array($item->meta))
                                            <span class="text-[11px] bg-slate-100 border border-slate-300 text-slate-700 px-2.5 py-0.5 rounded font-semibold">
                                                <i class="fa-regular fa-clock mr-1"></i>{{ $item->meta['start_time'] ?? '' }} - {{ $item->meta['end_time'] ?? '' }}
                                                · <i class="fa-solid fa-headset text-blue-600 mx-0.5"></i>{{ $item->meta['min_phone_ops'] ?? 2 }} Tel.
                                                · <i class="fa-solid fa-video text-indigo-600 mx-0.5"></i>{{ $item->meta['min_camera_ops'] ?? 4 }} Cám.
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3 px-5 text-center">
                                    <button wire:click="toggleItemActive({{ $item->id }})" class="text-xs font-bold px-2.5 py-0.5 rounded-full {{ $item->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">
                                        {{ $item->is_active ? 'Activo' : 'Inactivo' }}
                                    </button>
                                </td>
                            @elseif($activeTab === 'procedure_types')
                                <td class="py-3 px-5">
                                    @php $effFields = $item->effective_meta_fields; @endphp
                                    @if(count($effFields) > 0)
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($effFields as $f)
                                                <span class="text-[11px] bg-indigo-50 text-indigo-800 border border-indigo-200 px-2 py-0.5 rounded font-semibold">
                                                    {{ $f['label'] }}
                                                </span>
                                            @endforeach
                                            @if(empty($item->meta_fields))
                                                <span class="text-[10px] text-slate-400 italic self-center ml-1">(Sugerido automático)</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-400 italic">Estándar (Puedes configurar campos con "Editar")</span>
                                    @endif
                                </td>
                            @endif

                            <td class="py-3 px-5 text-center space-x-1.5 whitespace-nowrap">
                                <button wire:click="edit({{ $item->id }})" class="bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 px-3 py-1 rounded text-xs font-bold transition">
                                    <i class="fa-solid fa-pen mr-1"></i> Editar
                                </button>
                                <button onclick="confirm('¿Confirmas dar de baja / eliminar este registro?') || event.stopImmediatePropagation()" wire:click="delete({{ $item->id }})" class="bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 px-3 py-1 rounded text-xs font-bold transition">
                                    <i class="fa-solid fa-trash mr-1"></i> {{ isset($systemGroups[$activeTab]) ? 'Quitar (Soft-Delete)' : 'Eliminar' }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-400 font-semibold">No hay registros en este catálogo.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAPELERA / REGISTROS EN BAJA LÓGICA (SOFT-DELETE) CON OPCIÓN DE RESTAURAR --}}
        @if(isset($trashedItems) && $trashedItems->count() > 0)
            <div class="mt-6 bg-slate-50 border border-slate-200 rounded-xl p-4">
                <h4 class="text-xs font-black uppercase tracking-wider text-slate-600 mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-box-archive text-amber-600"></i>
                    <span>Registros Quitado(s) en Baja Lógica (Soft-Delete) — {{ $trashedItems->count() }}</span>
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5">
                    @foreach($trashedItems as $trashed)
                        <div class="bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 flex items-center justify-between text-xs shadow-sm">
                            <div>
                                <div class="font-bold text-slate-500 line-through">{{ $trashed->name }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">Baja: {{ $trashed->deleted_at?->format('d/m/Y H:i') }}</div>
                            </div>
                            <button type="button" wire:click="restore({{ $trashed->id }})" class="bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 px-2.5 py-1 rounded font-bold text-xs transition inline-flex items-center gap-1">
                                <i class="fa-solid fa-rotate-left"></i> Restaurar
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>