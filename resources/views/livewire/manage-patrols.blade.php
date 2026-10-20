<div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Distribución de Turnos y Patrullas</h2>
            <p class="text-gray-600 text-sm">Asigna el personal a los móviles según la pauta diaria.</p>
        </div>
    </div>

    <div class="bg-white p-4 rounded-lg shadow-sm border mb-6 flex gap-4 items-end bg-blue-50">
        <div>
            <label class="block text-sm font-bold text-blue-900">Fecha de Servicio</label>
            <input type="date" wire:model.live="filterDate" class="mt-1 rounded-md border-blue-300 shadow-sm">
        </div>
        <div>
            <label class="block text-sm font-bold text-blue-900">Bloque / Turno</label>
            <select wire:model.live="filterShift" class="mt-1 rounded-md border-blue-300 shadow-sm min-w-[250px]">
                @foreach($shiftBlocks as $sb)
                    <option value="{{ $sb }}">{{ $sb }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded shadow-sm">
            {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded shadow-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 bg-white shadow rounded-lg p-5 border-t-4 border-gray-800">
            <h3 class="font-bold text-lg text-gray-800 mb-4 border-b pb-2">Despachar Nuevo Móvil</h3>
            
            <form wire:submit.prevent="savePatrol">
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700">Vehículo / Móvil</label>
                    <select wire:model="device_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm bg-gray-50">
                        <option value="">Seleccione el móvil...</option>
                        @foreach($devices as $device)
                            <option value="{{ $device->id }}">{{ $device->name }}</option>
                        @endforeach
                    </select>
                    @error('device_id') <span class="text-red-600 text-xs font-semibold">{{ $message }}</span> @enderror
                </div>
                
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700">Sector (Opcional)</label>
                    <select wire:model="sector_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm bg-gray-50">
                        <option value="">Asignar a un sector...</option>
                        @foreach($sectors as $sector)
                            <option value="{{ $sector->id }}">{{ $sector->name }}</option>
                        @endforeach
                    </select>
                    @error('sector_id') <span class="text-red-600 text-xs font-semibold">{{ $message }}</span> @enderror
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Misiones Específicas / Puntos Fijos</label>
                    <textarea wire:model="mission_notes" rows="3" class="mt-1 w-full rounded-md border-gray-300 shadow-sm bg-gray-50" placeholder="Ej: 07:10 Liceo 131 Basica del Rodeo..."></textarea>
                </div>

                <div class="border border-gray-200 rounded p-3 bg-gray-50 mb-4">
                    <div class="flex justify-between items-center mb-2">
                        <label class="block text-sm font-bold text-gray-800">Tripulación (Inspectores)</label>
                        <button type="button" wire:click="addCrewMember" class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded font-bold hover:bg-blue-200">+ Agregar</button>
                    </div>
                    
                    @foreach($crewMembers as $index => $member)
                        <div class="flex gap-2 mb-2 items-start border-b pb-2 border-gray-200 last:border-0 last:pb-0">
                            <div class="flex-1 space-y-2">
                                <select wire:model="crewMembers.{{ $index }}.user_id" class="w-full text-sm rounded border-gray-300 shadow-sm">
                                    <option value="">Seleccionar Funcionario...</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                                @error("crewMembers.{$index}.user_id") <span class="text-red-600 text-xs block">{{ $message }}</span> @enderror
                                <div class="flex gap-2">
                                    <select wire:model="crewMembers.{{ $index }}.role" class="w-1/2 text-sm rounded border-gray-300 shadow-sm">
                                        @foreach($crewRoles as $cRole)
                                            <option value="{{ $cRole }}">{{ $cRole }}</option>
                                        @endforeach
                                    </select>
                                    <input type="text" wire:model="crewMembers.{{ $index }}.time_exception" placeholder="Ej: Hasta las 05:00" class="w-1/2 text-sm rounded border-gray-300 shadow-sm">
                                </div>
                            </div>
                            @if(count($crewMembers) > 1)
                                <button type="button" wire:click="removeCrewMember({{ $index }})" class="text-red-500 hover:text-red-700 mt-1">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>

                <button type="submit" class="w-full bg-gray-800 hover:bg-gray-900 text-white font-bold py-2 px-4 rounded shadow">
                    Guardar Patrulla
                </button>
            </form>
        </div>

        <div class="lg:col-span-2 space-y-4">
            <h3 class="font-bold text-lg text-gray-800 border-b pb-2">Patrullas Asignadas ({{ $filterShift }})</h3>
            
            @forelse($activePatrols as $patrol)
                <div class="bg-white border rounded-lg shadow-sm overflow-hidden flex {{ !$patrol->is_active ? 'opacity-60' : '' }}">
                    <div class="{{ $patrol->is_active ? 'bg-green-600' : 'bg-gray-400' }} w-2 flex-shrink-0"></div>
                    <div class="p-4 flex-1">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <h4 class="font-black text-xl text-blue-900 flex items-center gap-2">
                                    🚗 {{ $patrol->device->name ?? 'Sin Móvil' }}
                                    @if($patrol->sector)
                                        <span class="text-xs font-normal bg-blue-100 text-blue-800 px-2 py-1 rounded-full">
                                            Sector: {{ $patrol->sector->name }}
                                        </span>
                                    @endif
                                    @if($patrol->is_active)
                                        <span class="text-xs font-bold bg-green-100 text-green-800 px-2 py-0.5 rounded-full">
                                            ● En Turno
                                        </span>
                                    @else
                                        <span class="text-xs font-bold bg-gray-200 text-gray-600 px-2 py-0.5 rounded-full">
                                            Finalizado
                                        </span>
                                    @endif
                                </h4>
                            </div>
                            <div class="flex items-center gap-3">
                                <button wire:click="toggleActive({{ $patrol->id }})" class="text-xs font-bold px-2.5 py-1 rounded border {{ $patrol->is_active ? 'bg-yellow-50 text-yellow-800 border-yellow-300 hover:bg-yellow-100' : 'bg-green-50 text-green-800 border-green-300 hover:bg-green-100' }}">
                                    {{ $patrol->is_active ? 'Finalizar Turno' : 'Reactivar Turno' }}
                                </button>
                                <button onclick="confirm('¿Eliminar este registro de patrulla?') || event.stopImmediatePropagation()" wire:click="deletePatrol({{ $patrol->id }})" class="text-red-500 text-sm font-bold hover:underline">Eliminar</button>
                            </div>
                        </div>

                        @if($patrol->mission_notes)
                            <div class="bg-yellow-50 text-yellow-800 p-2 rounded text-sm mb-3 border border-yellow-200">
                                <strong>Misión:</strong> {{ $patrol->mission_notes }}
                            </div>
                        @endif

                        <div class="grid grid-cols-2 gap-2 mt-3 border-t pt-3">
                            @foreach($patrol->crew as $member)
                                <div class="flex items-center gap-2 text-sm border bg-gray-50 rounded p-2">
                                    <div class="font-bold text-gray-800">{{ $member->name }}</div>
                                    <div class="text-xs px-2 py-0.5 rounded bg-gray-200 text-gray-700">{{ $member->pivot->role }}</div>
                                    @if($member->pivot->time_exception)
                                        <div class="text-xs text-red-600 font-bold italic border-l pl-2">{{ $member->pivot->time_exception }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-gray-50 border-2 border-dashed border-gray-300 p-8 text-center rounded-lg">
                    <p class="text-gray-500 font-medium">No hay móviles asignados para este turno y fecha.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>