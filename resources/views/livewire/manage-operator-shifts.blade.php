<div class="py-6 max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

    {{-- CABECERA PRINCIPAL --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-800 tracking-tight flex items-center gap-2.5">
                <span class="bg-blue-600 text-white w-9 h-9 rounded-xl flex items-center justify-center shadow">
                    <i class="fa-solid fa-clipboard-check"></i>
                </span>
                <span>Distribución de Turnos de Sala CAD, Recepción y Cierre de Guardia</span>
            </h2>
            <p class="text-xs text-slate-500 mt-1">
                Arme los turnos rotativos de sala (08:00 a 14:00, 14:00 a 20:00, 20:00 a 08:00) asignando Operadores Telefónicos y Operadores de Cámaras (CCTV), registre bitácora y envíe el reporte ejecutivo a Directores al cierre.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @hasanyrole('Administrador|Supervisor')
                <button wire:click="$set('showRecipientsModal', true)" class="bg-slate-800 hover:bg-slate-900 text-white font-bold py-2 px-3.5 rounded-lg shadow text-xs flex items-center gap-2 transition">
                    <i class="fa-solid fa-envelope-circle-check text-blue-400"></i>
                    <span>Destinatarios Directores ({{ $recipients->where('is_active', true)->count() }})</span>
                </button>
            @endhasanyrole
            <a href="{{ route('dashboard') }}" class="bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 font-bold py-2 px-3.5 rounded-lg shadow-sm text-xs flex items-center gap-1.5 transition">
                <i class="fa-solid fa-arrow-left"></i> Volver a Sala Despacho
            </a>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 p-4 rounded-r-xl shadow-sm font-bold text-sm flex items-center gap-2.5">
            <i class="fa-solid fa-circle-check text-emerald-600 text-lg"></i>
            <span>{{ session('message') }}</span>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="bg-rose-50 border-l-4 border-rose-500 text-rose-800 p-4 rounded-r-xl shadow-sm font-bold text-sm flex items-center gap-2.5">
            <i class="fa-solid fa-triangle-exclamation text-rose-600 text-lg"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- BARRA DE SELECCIÓN DE PAUTA DIARIA Y BLOQUE ROTATIVO (IGUAL QUE TURNADO DE PATRULLAS) --}}
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
        <div class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-black uppercase text-blue-900 mb-1">
                    <i class="fa-regular fa-calendar-days mr-1"></i> Fecha de Servicio
                </label>
                <input type="date" wire:model.live="filterDate" class="rounded-lg border-blue-300 shadow-sm text-sm font-bold text-slate-800">
            </div>
            <div>
                <label class="block text-xs font-black uppercase text-blue-900 mb-1">
                    <i class="fa-solid fa-rotate mr-1"></i> Bloque / Turno Rotativo de Sala
                </label>
                <select wire:model.live="shift_type" class="rounded-lg border-blue-300 shadow-sm text-sm font-bold text-slate-800 min-w-[270px]">
                    @foreach($shiftBlocks as $sb)
                        <option value="{{ $sb }}">{{ $sb }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Resumen de Dotación Requerida según Bloque --}}
        <div class="flex flex-wrap items-center gap-2 text-xs">
            <span class="font-bold text-blue-900 uppercase text-[11px] mr-1">Dotación Sugerida Bloque:</span>
            <span class="px-3 py-1.5 rounded-lg font-black border inline-flex items-center gap-1.5 {{ $assignedPhoneOps >= ($blockQuota['min_phone_ops'] ?? 2) ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : 'bg-amber-100 text-amber-800 border-amber-300' }}">
                <i class="fa-solid fa-headset"></i>
                <span>Telefónicos: {{ $assignedPhoneOps }} / {{ $blockQuota['min_phone_ops'] ?? 2 }}</span>
            </span>
            <span class="px-3 py-1.5 rounded-lg font-black border inline-flex items-center gap-1.5 {{ $assignedCameraOps >= ($blockQuota['min_camera_ops'] ?? 4) ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : 'bg-amber-100 text-amber-800 border-amber-300' }}">
                <i class="fa-solid fa-video"></i>
                <span>Cámaras CCTV: {{ $assignedCameraOps }} / {{ $blockQuota['min_camera_ops'] ?? 4 }}</span>
            </span>
        </div>
    </div>

    {{-- SECCIÓN 1: CONSTRUCTOR DE DOTACIÓN DE TURNO DE OPERADORES + PAUTA DEL DÍA (IGUAL QUE PATRULLAS) --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        {{-- Columna Izquierda (5 cols): Armar Dotación del Turno de Sala --}}
        <div class="lg:col-span-5 bg-white shadow-sm rounded-xl p-5 border border-slate-200 border-t-4 border-t-slate-900">
            <div class="flex justify-between items-center border-b border-slate-200 pb-3 mb-4">
                <div>
                    <h3 class="font-black text-base text-slate-800 flex items-center gap-2">
                        <i class="fa-solid fa-users-gear text-blue-600"></i>
                        <span>{{ $editingShiftId ? 'Editar Dotación Turno #' . $editingShiftId : 'Armar Turno de Operadores' }}</span>
                    </h3>
                    <p class="text-[11px] text-slate-500">Asigne los operadores telefónicos y de cámaras para <strong>{{ $shift_type }}</strong>.</p>
                </div>
                <div class="flex gap-1">
                    <button type="button" wire:click="applySuggestedTemplate" class="text-[11px] bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 px-2.5 py-1 rounded-md font-bold transition" title="Pre-cargar filas para {{ $blockQuota['min_phone_ops'] ?? 2 }} Telefónicos y {{ $blockQuota['min_camera_ops'] ?? 4 }} Cámaras">
                        <i class="fa-solid fa-wand-magic-sparkles mr-1"></i> Plantilla {{ $blockQuota['min_phone_ops'] ?? 2 }}+{{ $blockQuota['min_camera_ops'] ?? 4 }}
                    </button>
                    <button type="button" wire:click="loadPreviousShiftCrew" class="text-[11px] bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 px-2.5 py-1 rounded-md font-bold transition" title="Cargar operadores del turno anterior para rotar">
                        <i class="fa-solid fa-rotate mr-1"></i> Rotar
                    </button>
                </div>
            </div>

            <div class="space-y-4">
                {{-- Listado dinámico de Operadores del Turno (Igual que Tripulación de Patrullas) --}}
                <div class="border border-slate-200 rounded-xl p-3.5 bg-slate-50">
                    <div class="flex justify-between items-center mb-2.5">
                        <label class="text-xs font-black uppercase text-slate-700">
                            <i class="fa-solid fa-user-group mr-1 text-blue-600"></i> Dotación Asignada ({{ count($crewMembers) }} puestos)
                        </label>
                        <div class="flex gap-1">
                            <button type="button" wire:click="addCrewMember('Operador Telefónico')" class="text-[11px] bg-blue-600 hover:bg-blue-700 text-white px-2.5 py-1 rounded font-bold transition">
                                + Telefónico
                            </button>
                            <button type="button" wire:click="addCrewMember('Operador de Cámaras (CCTV)')" class="text-[11px] bg-indigo-600 hover:bg-indigo-700 text-white px-2.5 py-1 rounded font-bold transition">
                                + Cámara CCTV
                            </button>
                        </div>
                    </div>

                    @error('crewMembers')
                        <div class="bg-rose-50 border border-rose-200 text-rose-700 text-xs font-bold px-3 py-1.5 rounded mb-2">
                            {{ $message }}
                        </div>
                    @enderror

                    <div class="space-y-2.5 max-h-[340px] overflow-y-auto pr-1">
                        @foreach($crewMembers as $index => $member)
                            @php
                                $isCamera = str_contains(strtolower($member['station_role'] ?? ''), 'cámara') || str_contains(strtolower($member['station_role'] ?? ''), 'cctv');
                            @endphp
                            <div class="bg-white border {{ $isCamera ? 'border-indigo-200' : 'border-blue-200' }} rounded-lg p-2.5 shadow-sm flex gap-2 items-start">
                                <div class="flex-1 space-y-2">
                                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                                        <div class="sm:col-span-7">
                                            <select wire:model="crewMembers.{{ $index }}.user_id" class="w-full text-xs font-bold rounded border-slate-300 shadow-sm">
                                                <option value="">-- Seleccionar Operador --</option>
                                                @foreach($operatorsList as $op)
                                                    <option value="{{ $op->id }}">
                                                        {{ $op->name }} {{ $op->extension ? '(Anexo '.$op->extension.')' : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="sm:col-span-5">
                                            <select wire:model.live="crewMembers.{{ $index }}.station_role" class="w-full text-xs font-bold rounded {{ $isCamera ? 'border-indigo-300 bg-indigo-50 text-indigo-900' : 'border-blue-300 bg-blue-50 text-blue-900' }}">
                                                @foreach($stationRoles as $sRole)
                                                    <option value="{{ $sRole }}">{{ $sRole }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <input type="text" wire:model="crewMembers.{{ $index }}.console_station" placeholder="Puesto (Ej: Anexo 101 / Muro 1)" class="w-full text-xs rounded border-slate-200">
                                        <input type="text" wire:model="crewMembers.{{ $index }}.time_exception" placeholder="Horario especial (Opcional)" class="w-full text-xs rounded border-slate-200">
                                    </div>
                                </div>
                                @if(count($crewMembers) > 1)
                                    <button type="button" wire:click="removeCrewMember({{ $index }})" class="text-rose-500 hover:text-rose-700 p-1">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Estado de Sistemas de Sala al Armar/Abrir --}}
                <div class="grid grid-cols-2 gap-2 bg-slate-50 p-3 rounded-xl border border-slate-200">
                    <div class="col-span-2 text-[11px] font-black uppercase text-slate-600">
                        <i class="fa-solid fa-server mr-1"></i> Estado de Equipos de Sala
                    </div>
                    @foreach([
                        'central_pbx' => 'Central PBX',
                        'radio_vhf' => 'Radio VHF',
                        'camaras_cctv' => 'Cámaras CCTV',
                        'gps_flota' => 'GPS Flota'
                    ] as $eqKey => $eqLabel)
                        <div class="flex items-center justify-between bg-white px-2.5 py-1.5 rounded border border-slate-200 text-xs">
                            <span class="font-bold text-slate-700">{{ $eqLabel }}</span>
                            <select wire:model="equipment_status.{{ $eqKey }}" class="text-[11px] font-bold border-0 py-0.5 pl-1 pr-5 rounded bg-slate-100">
                                <option value="Operativo">Operativo</option>
                                <option value="Con Intermitencia">Intermitente</option>
                                <option value="Fuera de Servicio">Falla</option>
                            </select>
                        </div>
                    @endforeach
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Observaciones de Apertura / Pauta</label>
                    <textarea wire:model="opening_notes" rows="2" class="w-full rounded-lg border-slate-300 text-xs" placeholder="Ej: Se recibe sala conforme, cámaras operativas en los 4 sectores..."></textarea>
                </div>

                {{-- Botones: Guardar Pauta vs Iniciar Turno Activo Ahora --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-1">
                    <button type="button"
                            wire:click="saveShiftRoster(false)"
                            class="w-full bg-slate-800 hover:bg-slate-900 text-white font-black py-2.5 px-4 rounded-xl shadow text-xs uppercase tracking-wider flex items-center justify-center gap-1.5 transition">
                        <i class="fa-solid fa-calendar-check"></i>
                        <span>{{ $editingShiftId ? 'Actualizar Pauta' : 'Guardar Pauta Turno' }}</span>
                    </button>

                    <button type="button"
                            wire:click="openNewShift"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-black py-2.5 px-4 rounded-xl shadow text-xs uppercase tracking-wider flex items-center justify-center gap-1.5 transition">
                        <i class="fa-solid fa-play"></i>
                        <span>Abrir e Iniciar Turno Ahora</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Columna Derecha (7 cols): Turnos Armados en la Fecha + Operación/Cierre del Turno Activo --}}
        <div class="lg:col-span-7 space-y-6">

            {{-- Tarjetas de Turnos Armados para la Fecha Seleccionada (Igual que Patrullas Asignadas) --}}
            <div class="bg-white shadow-sm rounded-xl p-5 border border-slate-200">
                <div class="flex justify-between items-center border-b border-slate-200 pb-3 mb-4">
                    <h3 class="font-black text-base text-slate-800 flex items-center gap-2">
                        <i class="fa-solid fa-calendar-day text-blue-600"></i>
                        <span>Turnos de Sala Armados ({{ \Carbon\Carbon::parse($filterDate)->format('d/m/Y') }})</span>
                    </h3>
                    <span class="text-xs font-bold text-slate-500">{{ $shiftsOfDate->count() }} turno(s) registrado(s)</span>
                </div>

                <div class="space-y-3">
                    @forelse($shiftsOfDate as $sItem)
                        @php
                            $phoneCrew = $sItem->crew->filter(fn($c) => str_contains(strtolower($c->pivot->station_role ?? ''), 'telef'));
                            $cameraCrew = $sItem->crew->filter(fn($c) => str_contains(strtolower($c->pivot->station_role ?? ''), 'cámara') || str_contains(strtolower($c->pivot->station_role ?? ''), 'cctv'));
                            $otherCrew = $sItem->crew->diff($phoneCrew)->diff($cameraCrew);
                        @endphp
                        <div class="border rounded-xl overflow-hidden flex {{ $sItem->is_active ? 'border-emerald-500 bg-emerald-50/20 shadow-sm' : 'border-slate-200 bg-white' }}">
                            <div class="{{ $sItem->is_active ? 'bg-emerald-500' : ($sItem->closed_at ? 'bg-slate-400' : 'bg-blue-600') }} w-2 shrink-0"></div>
                            <div class="p-4 flex-1">
                                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 mb-3">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-black text-base text-slate-900">{{ $sItem->shift_type }}</span>
                                        @if($sItem->is_active)
                                            <span class="bg-emerald-100 text-emerald-800 border border-emerald-300 text-[11px] font-black px-2.5 py-0.5 rounded-full uppercase">
                                                ● En Turno Activo
                                            </span>
                                        @elseif($sItem->closed_at)
                                            <span class="bg-slate-200 text-slate-700 text-[11px] font-bold px-2.5 py-0.5 rounded-full">
                                                Cerrado ({{ $sItem->closed_at->format('H:i') }})
                                            </span>
                                        @else
                                            <span class="bg-blue-100 text-blue-800 text-[11px] font-bold px-2.5 py-0.5 rounded-full">
                                                Pauta Planificada
                                            </span>
                                        @endif
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <button wire:click="editShiftRoster({{ $sItem->id }})" class="text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 px-2.5 py-1 rounded border border-slate-300">
                                            <i class="fa-solid fa-pen mr-1"></i> Editar Dotación
                                        </button>
                                        @if(!$sItem->is_active && !$sItem->closed_at)
                                            <button wire:click="activatePlannedShift({{ $sItem->id }})" class="text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white px-2.5 py-1 rounded shadow-sm">
                                                <i class="fa-solid fa-play mr-1"></i> Activar Turno
                                            </button>
                                        @endif
                                        @hasanyrole('Administrador|Supervisor')
                                            @if(!$sItem->is_active)
                                                <button onclick="confirm('¿Eliminar esta pauta de turno?') || event.stopImmediatePropagation()" wire:click="deleteShiftRoster({{ $sItem->id }})" class="text-rose-500 hover:text-rose-700 text-xs font-bold px-2 py-1">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            @endif
                                        @endhasanyrole
                                    </div>
                                </div>

                                @if($sItem->crew->count() > 0)
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                                        <div class="bg-blue-50/70 border border-blue-200 rounded-lg p-2.5">
                                            <div class="font-black text-blue-900 uppercase text-[10px] mb-1.5 flex items-center justify-between">
                                                <span><i class="fa-solid fa-headset mr-1"></i> Operadores Telefónicos</span>
                                                <span class="bg-blue-200 text-blue-900 px-1.5 py-0.2 rounded">{{ $phoneCrew->count() }}</span>
                                            </div>
                                            <div class="space-y-1">
                                                @forelse($phoneCrew as $op)
                                                    <div class="flex justify-between items-center bg-white px-2 py-1 rounded border border-blue-100">
                                                        <span class="font-bold text-slate-800">{{ $op->name }}</span>
                                                        <span class="text-[10px] text-blue-700 font-semibold">{{ $op->pivot->console_station ?: ($op->extension ? 'Anexo '.$op->extension : 'Central') }}</span>
                                                    </div>
                                                @empty
                                                    <span class="text-slate-400 italic text-[11px]">Sin operadores telefónicos asignados</span>
                                                @endforelse
                                            </div>
                                        </div>

                                        <div class="bg-indigo-50/70 border border-indigo-200 rounded-lg p-2.5">
                                            <div class="font-black text-indigo-900 uppercase text-[10px] mb-1.5 flex items-center justify-between">
                                                <span><i class="fa-solid fa-video mr-1"></i> Operadores de Cámaras (CCTV)</span>
                                                <span class="bg-indigo-200 text-indigo-900 px-1.5 py-0.2 rounded">{{ $cameraCrew->count() }}</span>
                                            </div>
                                            <div class="space-y-1">
                                                @forelse($cameraCrew as $op)
                                                    <div class="flex justify-between items-center bg-white px-2 py-1 rounded border border-indigo-100">
                                                        <span class="font-bold text-slate-800">{{ $op->name }}</span>
                                                        <span class="text-[10px] text-indigo-700 font-semibold">{{ $op->pivot->console_station ?: 'Muro CCTV' }}</span>
                                                    </div>
                                                @empty
                                                    <span class="text-slate-400 italic text-[11px]">Sin operadores de cámaras asignados</span>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>

                                    @if($otherCrew->count() > 0)
                                        <div class="mt-2 flex flex-wrap gap-1.5">
                                            @foreach($otherCrew as $op)
                                                <span class="text-[11px] bg-slate-100 border border-slate-300 text-slate-700 px-2.5 py-0.5 rounded-full font-bold">
                                                    {{ $op->name }} — {{ $op->pivot->station_role }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                @elseif(!empty($sItem->operators_on_duty))
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach($sItem->operators_on_duty as $opLabel)
                                            <span class="text-xs bg-slate-100 border border-slate-200 text-slate-700 px-2.5 py-1 rounded-md font-bold">
                                                <i class="fa-solid fa-user-check text-blue-600 mr-1"></i> {{ $opLabel }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-6 bg-slate-50 rounded-xl border border-dashed border-slate-300 text-slate-500 text-xs">
                            No hay turnos de sala armados para el {{ \Carbon\Carbon::parse($filterDate)->format('d/m/Y') }}. Utiliza el panel izquierdo para armar el Turno 1 (08:00-14:00), Turno 2 (14:00-20:00) o Turno 3 (20:00-08:00).
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- PANEL DE CIERRE Y TRASPASO DEL TURNO ACTIVO (CON ENVÍO DE EMAIL A DIRECTORES) --}}
            @if($activeShift)
                <div class="bg-slate-900 text-white rounded-xl p-5 shadow-lg border border-slate-800 space-y-4">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 border-b border-slate-800 pb-3">
                        <div>
                            <span class="text-[10px] font-black uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 px-2.5 py-0.5 rounded-full">
                                <i class="fa-solid fa-tower-broadcast animate-pulse mr-1"></i> Guardia en Curso (#{{ $activeShift->id }})
                            </span>
                            <h3 class="text-lg font-black mt-1">{{ $activeShift->shift_type }} — Bitácora y Entrega de Turno</h3>
                        </div>
                        <span class="text-xs text-slate-400 font-mono">Inicio: {{ $activeShift->started_at->format('d/m H:i') }} hrs</span>
                    </div>

                    @if($liveStats)
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                            <div class="bg-slate-800/90 border border-slate-700 rounded-lg p-2.5 text-center">
                                <span class="block text-xl font-black text-blue-400">{{ $liveStats['total_procedures'] ?? $liveStats['total_created'] ?? 0 }}</span>
                                <span class="text-[10px] uppercase font-bold text-slate-400">Casos del Turno</span>
                            </div>
                            <div class="bg-slate-800/90 border border-slate-700 rounded-lg p-2.5 text-center">
                                <span class="block text-xl font-black text-emerald-400">{{ $liveStats['closed_procedures'] ?? $liveStats['closed_in_shift'] ?? 0 }}</span>
                                <span class="text-[10px] uppercase font-bold text-slate-400">Resueltos</span>
                            </div>
                            <div class="bg-slate-800/90 border border-slate-700 rounded-lg p-2.5 text-center">
                                <span class="block text-xl font-black text-amber-400">{{ $liveStats['open_handover_count'] ?? $liveStats['pending_handover_count'] ?? 0 }}</span>
                                <span class="text-[10px] uppercase font-bold text-slate-400">Abiertos (Traspaso)</span>
                            </div>
                            <div class="bg-slate-800/90 border border-slate-700 rounded-lg p-2.5 text-center">
                                <span class="block text-xl font-black text-indigo-400">{{ $liveStats['total_calls'] ?? $liveStats['calls_total'] ?? 0 }}</span>
                                <span class="text-[10px] uppercase font-bold text-slate-400">Llamadas PBX</span>
                            </div>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-300 mb-1">
                                <i class="fa-solid fa-book mr-1 text-blue-400"></i> Bitácora de Novedades de Sala
                            </label>
                            <textarea wire:model="shift_log_notes" rows="3" class="w-full rounded-lg bg-slate-950 border-slate-700 text-white text-xs" placeholder="Anote novedades relevantes ocurridas durante el turno..."></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-amber-300 mb-1">
                                <i class="fa-solid fa-triangle-exclamation mr-1 text-amber-400"></i> Consignas / Pendientes para el Turno Entrante
                            </label>
                            <textarea wire:model="handover_notes" rows="3" class="w-full rounded-lg bg-slate-950 border-slate-700 text-white text-xs" placeholder="Instrucciones o casos prioritarios que recibe el turno entrante..."></textarea>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-stretch sm:items-end justify-between gap-3 pt-2 border-t border-slate-800">
                        <div class="flex-1">
                            <label class="block text-xs font-bold uppercase text-slate-300 mb-1">
                                Operador / Jefe de Sala Entrante que Recibe el Turno
                            </label>
                            <select wire:model="received_by_user_id" class="w-full rounded-lg bg-slate-950 border-slate-700 text-white text-xs font-bold">
                                <option value="">-- Seleccione Operador Entrante (Relevo) --</option>
                                @foreach($operatorsList as $op)
                                    <option value="{{ $op->id }}">{{ $op->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex gap-2">
                            <button type="button" wire:click="saveShiftProgress" class="bg-slate-700 hover:bg-slate-600 text-white font-bold py-2.5 px-4 rounded-lg text-xs transition">
                                <i class="fa-solid fa-floppy-disk mr-1"></i> Guardar Avance
                            </button>
                            <button type="button"
                                    onclick="confirm('¿Confirmas el CIERRE DE TURNO y el envío automático del reporte estadístico por correo a los Directores?') || event.stopImmediatePropagation()"
                                    wire:click="closeAndHandoverShift"
                                    class="bg-rose-600 hover:bg-rose-500 text-white font-black py-2.5 px-4 rounded-lg shadow text-xs uppercase tracking-wider transition">
                                <i class="fa-solid fa-paper-plane mr-1"></i> Cerrar Turno y Enviar Email
                            </button>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

    {{-- HISTORIAL DE ACTAS DE TURNOS CERRADOS Y VISTA PREVIA DE EMAIL --}}
    <div class="bg-white shadow-sm rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 bg-slate-900 text-white flex justify-between items-center">
            <h3 class="text-sm font-black uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-blue-400"></i>
                <span>Historial de Turnos Cerrados y Reportes Enviados a Dirección</span>
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-xs">
                <thead class="bg-slate-50 font-black uppercase text-slate-600">
                    <tr>
                        <th class="px-4 py-3 text-left">Turno / Bloque</th>
                        <th class="px-4 py-3 text-left">Horario Ejecución</th>
                        <th class="px-4 py-3 text-left">Dotación (Tel. / Cámaras)</th>
                        <th class="px-4 py-3 text-left">Relevo Entrante</th>
                        <th class="px-4 py-3 text-center">KPIs Guardia</th>
                        <th class="px-4 py-3 text-center">Reporte Email</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($pastShifts as $ps)
                        @php $st = $ps->stats_snapshot ?? []; @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-bold text-slate-800">
                                #{{ $ps->id }} · {{ $ps->shift_type }}
                            </td>
                            <td class="px-4 py-3 font-mono text-slate-600 whitespace-nowrap">
                                {{ $ps->started_at?->format('d/m H:i') }} → {{ $ps->closed_at?->format('d/m H:i') ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-3">
                                @if($ps->crew->count() > 0)
                                    <span class="font-bold text-blue-700">{{ $ps->crew->count() }} operadores</span>
                                    <span class="text-slate-500 block truncate max-w-xs">{{ $ps->crew->pluck('name')->join(', ') }}</span>
                                @elseif(!empty($ps->operators_on_duty))
                                    <span class="text-slate-700 font-semibold">{{ implode(', ', $ps->operators_on_duty) }}</span>
                                @else
                                    <span class="text-slate-500">{{ $ps->openedBy->name ?? 'N/A' }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-semibold text-slate-700">
                                {{ $ps->receivedBy->name ?? 'Sin especificar' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="bg-blue-50 text-blue-800 border border-blue-200 px-2 py-0.5 rounded font-bold">
                                    {{ $st['total_created'] ?? 0 }} casos · {{ $st['calls_total'] ?? 0 }} llamadas
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center space-x-1.5 whitespace-nowrap">
                                <a href="{{ route('shifts.email-preview', $ps->id) }}" target="_blank" class="bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 px-2.5 py-1 rounded font-bold inline-flex items-center gap-1">
                                    <i class="fa-solid fa-eye"></i> Ver Plantilla
                                </a>
                                <button wire:click="resendShiftEmail({{ $ps->id }})" class="bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 px-2.5 py-1 rounded font-bold inline-flex items-center gap-1">
                                    <i class="fa-solid fa-paper-plane"></i> Reenviar
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-400 font-semibold">
                                Aún no hay turnos cerrados en el historial.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($pastShifts->hasPages())
            <div class="px-4 py-3 border-t border-slate-200 bg-slate-50">
                {{ $pastShifts->links() }}
            </div>
        @endif
    </div>

    {{-- MODAL DE DESTINATARIOS DE CORREO (DIRECTORES) --}}
    @if($showRecipientsModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-200">
                <div class="bg-slate-900 text-white px-5 py-4 flex justify-between items-center">
                    <h3 class="font-black text-sm uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-envelope-circle-check text-blue-400"></i>
                        <span>Nómina de Directores para Reporte de Cierre de Turno</span>
                    </h3>
                    <button wire:click="$set('showRecipientsModal', false)" class="text-slate-400 hover:text-white">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div class="p-5 space-y-4">
                    @if(session()->has('recipient_message'))
                        <div class="bg-emerald-50 text-emerald-800 border border-emerald-200 px-3 py-2 rounded text-xs font-bold">
                            {{ session('recipient_message') }}
                        </div>
                    @endif

                    <form wire:submit.prevent="addRecipient" class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-end bg-slate-50 p-3 rounded-xl border border-slate-200">
                        <div class="sm:col-span-4">
                            <label class="block text-[10px] font-bold uppercase text-slate-600">Nombre Director</label>
                            <input type="text" wire:model="recipient_name" placeholder="Ej: Director DSP" class="mt-0.5 w-full rounded border-slate-300 text-xs">
                        </div>
                        <div class="sm:col-span-5">
                            <label class="block text-[10px] font-bold uppercase text-slate-600">Correo Electrónico</label>
                            <input type="email" wire:model="recipient_email" placeholder="director@buin.cl" class="mt-0.5 w-full rounded border-slate-300 text-xs">
                        </div>
                        <div class="sm:col-span-3">
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-1.5 px-2 rounded text-xs">
                                + Agregar
                            </button>
                        </div>
                    </form>

                    <div class="divide-y divide-slate-200 border rounded-xl overflow-hidden max-h-60 overflow-y-auto">
                        @foreach($recipients as $rec)
                            <div class="px-3.5 py-2.5 flex items-center justify-between text-xs bg-white">
                                <div>
                                    <div class="font-bold text-slate-800">{{ $rec->name }}</div>
                                    <div class="text-slate-500 font-mono text-[11px]">{{ $rec->email }}</div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button wire:click="toggleRecipient({{ $rec->id }})" class="px-2 py-0.5 rounded font-bold text-[10px] {{ $rec->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">
                                        {{ $rec->is_active ? 'Activo' : 'Pausado' }}
                                    </button>
                                    <button wire:click="deleteRecipient({{ $rec->id }})" class="text-rose-500 hover:text-rose-700 px-1.5">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
