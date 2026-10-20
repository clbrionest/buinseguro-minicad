<div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Historial Telefónico (Integración PBX)</h2>
            <p class="text-sm text-gray-500">Listado de todos los llamados recibidos en la central.</p>
        </div>
        
        <div class="w-64">
            <input wire:model.live="search" type="text" placeholder="Buscar por número o anexo..." class="w-full border rounded-lg px-4 py-2 shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>
    </div>

    <div class="bg-white shadow-md rounded-lg overflow-hidden border border-gray-200">
        <table class="min-w-full leading-normal">
            <thead>
                <tr class="bg-gray-800 text-white text-xs uppercase tracking-wider">
                    <th class="py-3 px-6 text-left font-bold">Fecha / Hora</th>
                    <th class="py-3 px-6 text-left font-bold">Número Origen</th>
                    <th class="py-3 px-6 text-center font-bold">Anexo Receptor</th>
                    <th class="py-3 px-6 text-center font-bold">Estado PBX</th>
                    <th class="py-3 px-6 text-center font-bold">Grabación</th>
                    <th class="py-3 px-6 text-center font-bold">Caso / Clasificación</th>
                    <th class="py-3 px-6 text-center font-bold">Acción</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                @forelse ($calls as $call)
                    <tr class="border-b border-gray-200 hover:bg-gray-50 transition">
                        <td class="py-3 px-6">
                            <div class="font-bold text-gray-900">{{ $call->created_at->format('d/m/Y') }}</div>
                            <div class="text-xs text-gray-500">{{ $call->created_at->format('H:i:s') }}</div>
                        </td>
                        <td class="py-3 px-6 font-mono font-bold text-blue-700">
                            {{ $call->caller_id }}
                        </td>
                        <td class="py-3 px-6 text-center">
                            <span class="bg-gray-100 px-2 py-1 rounded border text-gray-600 font-bold">
                                📞 {{ $call->extension }}
                            </span>
                        </td>
                        <td class="py-3 px-6 text-center">
                            @php
                                $statusColors = match($call->status) {
                                    'ringing' => 'bg-blue-100 text-blue-700',
                                    'completed' => 'bg-green-100 text-green-700',
                                    default => 'bg-gray-100 text-gray-600'
                                };
                            @endphp
                            <span class="{{ $statusColors }} px-3 py-1 rounded-full text-xs font-bold uppercase">
                                {{ $call->status }}
                            </span>
                        </td>

                        <td class="py-3 px-6 text-center">
                            @if($call->recording_path)
                                <audio controls class="h-8 w-40 inline-block" preload="none">
                                    <source src="{{ route('calls.play', $call->id) }}" type="audio/wav">
                                </audio>
                            @else
                                <span class="text-xs text-gray-400 italic">No disponible</span>
                            @endif
                        </td>
                        <td class="py-3 px-6 text-center">
                            @if($call->procedure_id)
                                <div class="flex flex-col items-center">
                                    <span class="bg-indigo-100 text-indigo-700 px-3 py-1 rounded-full text-xs font-bold border border-indigo-200">
                                        VINCULADO
                                    </span>
                                    <span class="text-[10px] text-gray-400 mt-1">Caso #{{ $call->procedure_id }}</span>
                                </div>
                            @else
                                <span class="bg-gray-100 text-gray-400 px-3 py-1 rounded-full text-xs font-bold">
                                    SIN CASO
                                </span>
                            @endif
                        </td>
                        <td class="py-3 px-6 text-center">
                            @if($call->procedure_id)
                                <a href="{{ route('procedures.show', $call->procedure_id) }}" class="text-blue-600 hover:text-blue-900 font-bold underline text-xs">
                                    Ver Caso →
                                </a>
                            @else
                                @if($call->classification)
                                    <div class="flex flex-col items-center">
                                        <span class="text-xs font-bold text-gray-700 bg-gray-200 px-2 py-1 rounded">
                                            {{ $call->classification }}
                                        </span>
                                        <button wire:click="openClassifyModal({{ $call->id }})" class="text-[10px] text-blue-500 hover:underline mt-1">Editar</button>
                                    </div>
                                @else
                                    <button wire:click="openClassifyModal({{ $call->id }})" class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-xs font-bold shadow transition">
                                        + Tipificar
                                    </button>
                                @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-10 text-center text-gray-500 italic">
                            No se encontraron registros de llamadas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        
        <div class="px-6 py-4 bg-gray-50 border-t">
            {{ $calls->links() }}
        </div>
    </div>
    @if($isClassifyModalOpen)
    <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center">
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" wire:click="closeClassifyModal"></div>

        <div class="relative bg-white rounded-lg max-w-md w-full mx-4 shadow-xl z-50">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 rounded-t-lg">
                <h3 class="text-lg font-bold text-gray-900">Tipificar Llamada sin Procedimiento</h3>
            </div>
            
            <div class="px-6 py-4">
                <div class="mb-4">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Motivo / Clasificación</label>
                    <select wire:model="classification" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                        <option value="">-- Seleccione un motivo --</option>
                        @foreach($callClassifications as $cOption)
                            <option value="{{ $cOption }}">{{ $cOption }}</option>
                        @endforeach
                    </select>
                    @error('classification') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Notas Adicionales (Opcional)</label>
                    <textarea wire:model="notes" rows="3" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" placeholder="Ej: Vecino preguntaba por el horario de la feria..."></textarea>
                    @error('notes') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-2 rounded-b-lg">
                <button wire:click="closeClassifyModal" class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-bold text-gray-700 hover:bg-gray-50">
                    Cancelar
                </button>
                <button wire:click="saveClassification" class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-bold text-white hover:bg-blue-700">
                    Guardar Tipificación
                </button>
            </div>
        </div>
    </div>
@endif
</div>