<div>
    @if($showModal)
        <div class="fixed bottom-8 right-8 z-50 bg-white border-l-8 border-green-500 shadow-2xl rounded-lg p-6 w-96 animate-bounce-short">
            
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-3">
                    <span class="relative flex h-4 w-4">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-4 w-4 bg-green-500"></span>
                    </span>
                    <h3 class="text-lg font-bold text-gray-800 uppercase tracking-wider">¡Llamada Entrante!</h3>
                </div>
                
                <button wire:click="dismiss" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <div class="mb-4 p-3 bg-gray-50 rounded border text-center">
                <p class="text-xs font-bold text-gray-500 uppercase mb-1">Teléfono Origen</p>
                <p class="font-black text-3xl text-gray-900 tracking-widest">{{ $callerId }}</p>
                @if($extension)
                    <p class="text-xs font-semibold text-green-700 mt-1">Anexo Receptor: 📞 {{ $extension }}</p>
                @endif
                <div class="mt-2 flex flex-wrap justify-center gap-1.5">
                    <span class="text-[11px] font-bold px-2 py-0.5 rounded bg-blue-100 text-blue-800">
                        {{ $previousCallsCount }} llamadas previas
                    </span>
                    @if($prankCallsCount > 0)
                        <span class="text-[11px] font-bold px-2 py-0.5 rounded bg-red-100 text-red-800 animate-pulse">
                            ⚠️ {{ $prankCallsCount }} Pitanza/Broma
                        </span>
                    @endif
                </div>
            </div>

            @if(count($recentProcedures) > 0)
                <div class="mb-4 p-2.5 bg-blue-50 border border-blue-200 rounded text-xs">
                    <p class="font-bold text-blue-900 uppercase text-[10px] mb-1.5">📋 Últimos Procedimientos de este número:</p>
                    <div class="space-y-1.5 max-h-28 overflow-y-auto">
                        @foreach($recentProcedures as $proc)
                            <div class="bg-white p-1.5 rounded border border-blue-100 flex justify-between items-center">
                                <div class="truncate pr-2">
                                    <span class="font-bold text-gray-800">#{{ $proc['id'] }} {{ $proc['type'] }}</span>
                                    <div class="text-[10px] text-gray-500 truncate">{{ $proc['address'] }}</div>
                                </div>
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-gray-100 text-gray-700 whitespace-nowrap">{{ $proc['date'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
            
            <div class="flex space-x-3">
                <button wire:click="createProcedure" class="w-full bg-green-600 hover:bg-green-700 text-white font-black py-3 px-4 rounded-lg shadow-md flex items-center justify-center gap-2 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                    ATENDER Y CREAR CASO
                </button>
            </div>
        </div>

        <style>
            .animate-bounce-short {
                animation: bounce-short 0.5s ease-out 3;
            }
            @keyframes bounce-short {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-10%); }
            }
        </style>
    @endif
</div>