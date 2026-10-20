<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <!-- Header & Security KPIs -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 text-white shadow-lg flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <div class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-blue-400 mb-1">
                    <i class="fa-solid fa-shield-halved"></i> Seguridad Institucional y Trazabilidad
                </div>
                <h1 class="text-2xl font-black tracking-tight flex items-center gap-2">
                    Registro de Auditoría y Eventos de Seguridad CAD
                </h1>
                <p class="text-xs text-slate-400 mt-1">
                    Bitácora inmutable de accesos, creaciones y cambios de estado de procedimientos, aperturas/cierres de guardia, reproducción de audios PBX e intentos de acceso denegados.
                </p>
            </div>

            <div class="flex items-center gap-3 shrink-0">
                <div class="bg-slate-800 border border-slate-700 rounded-xl px-4 py-2.5 text-center">
                    <span class="block text-xl font-black text-blue-400">{{ $todayEventsCount }}</span>
                    <span class="text-[10px] uppercase font-bold text-slate-400">Eventos Hoy</span>
                </div>
                <div class="bg-slate-800 border {{ $securityAlertsCount > 0 ? 'border-rose-500/60 bg-rose-950/40' : 'border-slate-700' }} rounded-xl px-4 py-2.5 text-center">
                    <span class="block text-xl font-black {{ $securityAlertsCount > 0 ? 'text-rose-400' : 'text-emerald-400' }}">{{ $securityAlertsCount }}</span>
                    <span class="text-[10px] uppercase font-bold text-slate-400">Accesos Denegados Hoy</span>
                </div>
            </div>
        </div>

        <!-- Filters Bar -->
        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">
                        <i class="fa-solid fa-magnifying-glass mr-1 text-slate-400"></i> Buscar en Descripción / Acción / IP
                    </label>
                    <input type="text"
                           wire:model.live.debounce.300ms="search"
                           placeholder="Ej: Folio #12, audio_played, 192.168..."
                           class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Módulo</label>
                    <select wire:model.live="filterModule" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Todos los módulos</option>
                        @foreach($modules as $mod)
                            <option value="{{ $mod }}">{{ $mod }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Usuario / Operador</label>
                    <select wire:model.live="filterUser" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Todos los usuarios</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <div class="flex-1">
                        <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Fecha</label>
                        <input type="date" wire:model.live="filterDate" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
                    </div>
                    @if($search || $filterModule || $filterUser || $filterDate)
                        <button type="button" wire:click="clearFilters" class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold px-3 py-2 rounded-lg text-xs h-[38px]" title="Limpiar filtros">
                            <i class="fa-solid fa-rotate-left"></i>
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Audit Logs Table -->
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-xs font-black uppercase text-slate-600">
                        <tr>
                            <th class="px-4 py-3 text-left">Fecha / Hora</th>
                            <th class="px-4 py-3 text-left">Usuario</th>
                            <th class="px-4 py-3 text-left">Módulo / Acción</th>
                            <th class="px-4 py-3 text-left">Detalle del Evento</th>
                            <th class="px-4 py-3 text-left">IP Origen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($logs as $log)
                            @php
                                $isDenied = $log->action === 'security.denied';
                            @endphp
                            <tr class="{{ $isDenied ? 'bg-rose-50/70' : 'hover:bg-slate-50' }}">
                                <td class="px-4 py-3 whitespace-nowrap font-mono text-xs text-slate-600">
                                    <div class="font-bold text-slate-800">{{ $log->created_at->format('d/m/Y') }}</div>
                                    <div>{{ $log->created_at->format('H:i:s') }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($log->user)
                                        <div class="font-bold text-slate-800 text-xs">{{ $log->user->name }}</div>
                                        <div class="text-[11px] text-slate-500">{{ $log->user->email }}</div>
                                    @else
                                        <span class="text-xs text-slate-400 italic">Sistema / Anónimo</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-black uppercase {{ $isDenied ? 'bg-rose-100 text-rose-800' : 'bg-blue-100 text-blue-800' }}">
                                        {{ $log->module }}
                                    </span>
                                    <div class="font-mono text-[11px] text-slate-500 mt-0.5">{{ $log->action }}</div>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-700">
                                    <div class="font-semibold {{ $isDenied ? 'text-rose-800 font-bold' : 'text-slate-800' }}">
                                        {{ $log->description }}
                                    </div>
                                    @if(!empty($log->metadata))
                                        <div class="mt-1 text-[11px] font-mono text-slate-500 bg-slate-100 rounded px-2 py-1 inline-block max-w-xl truncate">
                                            {{ json_encode($log->metadata, JSON_UNESCAPED_UNICODE) }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap font-mono text-xs text-slate-500">
                                    {{ $log->ip_address ?? 'N/A' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-slate-400 text-sm">
                                    <i class="fa-solid fa-shield-check text-2xl mb-2 block text-slate-300"></i>
                                    No se encontraron registros de auditoría con los filtros seleccionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
                <div class="px-4 py-3 border-t border-slate-200 bg-slate-50">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
