<div class="py-6 max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

    {{-- CABECERA DEL MÓDULO --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-ui-card p-5 rounded-2xl border border-ui-border shadow-xs">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-brand-cobalt text-white flex items-center justify-center text-lg font-black shadow-xs">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div>
                <span class="text-[10px] font-extrabold uppercase tracking-widest text-brand-lime block">MÓDULO OPERATIVO</span>
                <h2 class="text-xl font-black text-ui-fg">Patrullajes Preventivos de Terreno</h2>
                <p class="text-xs text-ui-muted">Planificación de rondas, validación de hitos por GPS y registro de ruta preventiva.</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button type="button"
                    wire:click="$dispatch('open-create-patrol-modal')"
                    class="bg-brand-cobalt hover:bg-brand-cobalt-deep text-brand-white font-extrabold py-2 px-4 rounded-lg shadow-xs text-xs inline-flex items-center gap-1.5 transition min-h-[38px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ui-focus">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                <span>Ingresar Patrullaje Preventivo</span>
            </button>
        </div>
    </div>

    {{-- ALERTAS DE SESIÓN --}}
    @if (session()->has('message'))
        <div class="p-3 bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 dark:text-emerald-300 rounded-xl text-xs font-bold flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-circle-check"></i>
                <span>{{ session('message') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">✕</button>
        </div>
    @endif

    {{-- TARJETAS KPI DE MONITOREO --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-ui-card p-4 rounded-xl border border-ui-border shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-emerald-500/15 text-emerald-600 flex items-center justify-center text-lg font-black">
                <span class="relative flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                </span>
            </div>
            <div>
                <span class="text-[11px] font-bold text-ui-muted uppercase tracking-wider block">Patrullajes En Curso</span>
                <span class="text-2xl font-black font-mono text-ui-fg">{{ $activeCount }}</span>
            </div>
        </div>

        <div class="bg-ui-card p-4 rounded-xl border border-ui-border shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-blue-500/15 text-blue-600 flex items-center justify-center text-lg font-black">
                <i class="fa-solid fa-flag-checkered"></i>
            </div>
            <div>
                <span class="text-[11px] font-bold text-ui-muted uppercase tracking-wider block">Completados Hoy</span>
                <span class="text-2xl font-black font-mono text-ui-fg">{{ $completedToday }}</span>
            </div>
        </div>

        <div class="bg-ui-card p-4 rounded-xl border border-ui-border shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-amber-500/15 text-amber-600 flex items-center justify-center text-lg font-black">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div>
                <span class="text-[11px] font-bold text-ui-muted uppercase tracking-wider block">Casos Iniciados desde Patrullaje</span>
                <span class="text-2xl font-black font-mono text-ui-fg">{{ $proceduresFromPatrolsToday }}</span>
            </div>
        </div>
    </div>

    {{-- BARRA DE FILTROS --}}
    <div class="bg-ui-card p-4 rounded-xl border border-ui-border shadow-xs flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2 flex-1 min-w-[280px]">
            {{-- Buscador --}}
            <div class="relative flex-1 min-w-[200px]">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-xs text-ui-muted"></i>
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Buscar por Folio, Móvil o Misión..."
                       class="w-full pl-8 pr-3 py-1.5 text-xs rounded-lg border-ui-border bg-ui-subtle text-ui-fg placeholder:text-ui-muted focus:ring-brand-cobalt">
            </div>

            {{-- Filtro de Sector --}}
            <select wire:model.live="filterSector" class="text-xs rounded-lg border-ui-border bg-ui-subtle text-ui-fg font-bold">
                <option value="">Todos los Sectores</option>
                @foreach($sectors as $sec)
                    <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                @endforeach
            </select>

            {{-- Filtro de Fecha --}}
            <input type="date"
                   wire:model.live="filterDate"
                   class="text-xs rounded-lg border-ui-border bg-ui-subtle text-ui-fg font-mono">
        </div>

        {{-- Tabs de Estado --}}
        <div class="flex items-center gap-1 bg-ui-subtle p-1 rounded-lg border border-ui-border text-xs font-bold">
            <button type="button"
                    wire:click="$set('filterStatus', 'all')"
                    class="px-2.5 py-1 rounded-md transition {{ $filterStatus === 'all' ? 'bg-ui-card text-ui-fg shadow-xs' : 'text-ui-muted hover:text-ui-fg' }}">
                Todos
            </button>
            <button type="button"
                    wire:click="$set('filterStatus', 'En Curso')"
                    class="px-2.5 py-1 rounded-md transition {{ $filterStatus === 'En Curso' ? 'bg-emerald-500 text-white shadow-xs' : 'text-ui-muted hover:text-ui-fg' }}">
                En Curso
            </button>
            <button type="button"
                    wire:click="$set('filterStatus', 'Completado')"
                    class="px-2.5 py-1 rounded-md transition {{ $filterStatus === 'Completado' ? 'bg-ui-card text-ui-fg shadow-xs' : 'text-ui-muted hover:text-ui-fg' }}">
                Completados
            </button>
        </div>
    </div>

    {{-- TABLA DE PATRULLAJES PREVENTIVOS --}}
    <div class="bg-ui-card rounded-xl border border-ui-border shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-ui-subtle text-ui-muted uppercase text-[10px] tracking-wider border-b border-ui-border font-black">
                    <tr>
                        <th class="py-3 px-4">Folio / Tipo</th>
                        <th class="py-3 px-4">Móvil & Tripulación</th>
                        <th class="py-3 px-4">Sector</th>
                        <th class="py-3 px-4">Horario & Duración</th>
                        <th class="py-3 px-4 text-center">Validación de Hitos</th>
                        <th class="py-3 px-4 text-center">Casos Iniciados</th>
                        <th class="py-3 px-4 text-center">Estado</th>
                        <th class="py-3 px-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ui-border font-medium">
                    @forelse($patrols as $p)
                        <tr class="hover:bg-ui-subtle/60 transition">
                            {{-- Folio & Tipo --}}
                            <td class="py-3 px-4">
                                <a href="{{ route('patrols.preventive.show', $p) }}" wire:navigate class="font-mono font-black text-brand-cobalt dark:text-brand-cobalt-light hover:underline text-sm block">
                                    {{ $p->folio }}
                                </a>
                                <span class="text-[11px] text-ui-muted block truncate max-w-[200px]" title="{{ $p->patrol_type }}">
                                    {{ $p->patrol_type }}
                                </span>
                            </td>

                            {{-- Móvil & Tripulación --}}
                            <td class="py-3 px-4">
                                <div class="font-extrabold text-ui-fg flex items-center gap-1.5">
                                    <span>🚓</span>
                                    <span>{{ $p->device?->name ?? 'Sin móvil' }}</span>
                                </div>
                                <div class="text-[11px] text-ui-muted truncate max-w-[180px]" title="{{ $p->patrolAssignment?->crew?->pluck('name')->join(', ') ?: 'Sin tripulación' }}">
                                    {{ $p->patrolAssignment?->crew?->pluck('name')->join(', ') ?: 'Registrado por: '.$p->user?->name }}
                                </div>
                            </td>

                            {{-- Sector --}}
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700">
                                    {{ $p->sector?->name ?? 'Comuna General' }}
                                </span>
                            </td>

                            {{-- Horario & Duración --}}
                            <td class="py-3 px-4 font-mono text-[11px]">
                                <div>Inicio: <strong class="text-ui-fg">{{ $p->started_at->format('d/m H:i') }}</strong></div>
                                <div>Fin: <span class="text-ui-muted">{{ $p->ended_at ? $p->ended_at->format('d/m H:i') : 'En curso...' }}</span></div>
                                <div class="text-[10px] text-brand-cobalt dark:text-brand-cobalt-mid font-bold mt-0.5">⏱ {{ $p->duration_formatted }}</div>
                            </td>

                            {{-- Validación de Hitos --}}
                            <td class="py-3 px-4 text-center">
                                @php
                                    $validated = $p->validated_checkpoints_count;
                                    $total = $p->total_checkpoints_count;
                                    $percent = $p->validation_percentage;
                                @endphp
                                <div class="flex flex-col items-center">
                                    <span class="font-bold text-xs {{ $percent === 100 ? 'text-emerald-600' : 'text-ui-fg' }}">
                                        {{ $validated }} / {{ $total }} hitos
                                    </span>
                                    <div class="w-24 bg-slate-200 dark:bg-slate-700 h-1.5 rounded-full overflow-hidden mt-1">
                                        <div class="h-full rounded-full transition-all duration-300 {{ $percent === 100 ? 'bg-emerald-500' : ($percent > 50 ? 'bg-blue-500' : 'bg-amber-500') }}"
                                             style="width: {{ $percent }}%"></div>
                                    </div>
                                    <span class="text-[10px] text-ui-muted mt-0.5 font-mono">{{ $percent }}%</span>
                                </div>
                            </td>

                            {{-- Casos Iniciados --}}
                            <td class="py-3 px-4 text-center">
                                @if($p->procedures->count() > 0)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-black bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300 border border-red-300">
                                        <i class="fa-solid fa-triangle-exclamation text-[10px]"></i>
                                        <span>{{ $p->procedures->count() }}</span>
                                    </span>
                                @else
                                    <span class="text-ui-muted font-mono text-xs">0</span>
                                @endif
                            </td>

                            {{-- Estado --}}
                            <td class="py-3 px-4 text-center">
                                @if($p->status === 'En Curso')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300 animate-pulse">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                        <span>En Curso</span>
                                    </span>
                                @elseif($p->status === 'Completado')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 border border-blue-300">
                                        ✓ Completado
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                        {{ $p->status }}
                                    </span>
                                @endif
                            </td>

                            {{-- Acciones --}}
                            <td class="py-3 px-4 text-right space-x-1 whitespace-nowrap">
                                {{-- Ver Detalle --}}
                                <a href="{{ route('patrols.preventive.show', $p) }}"
                                   wire:navigate
                                   class="p-1.5 rounded-lg text-ui-muted hover:text-brand-cobalt hover:bg-ui-subtle inline-block"
                                   title="Ver Detalle y Validación de Hitos">
                                    <i class="fa-solid fa-eye"></i>
                                </a>

                                {{-- Emitir Informe / Imprimir --}}
                                <a href="{{ route('patrols.preventive.print', $p) }}"
                                   target="_blank"
                                   class="p-1.5 rounded-lg text-ui-muted hover:text-emerald-600 hover:bg-ui-subtle inline-block"
                                   title="Emitir Informe Oficial de Patrullaje">
                                    <i class="fa-solid fa-file-pdf"></i>
                                </a>

                                {{-- Iniciar Procedimiento desde este Patrullaje --}}
                                @if($p->status === 'En Curso')
                                    <button type="button"
                                            wire:click="$dispatch('openModalWithPatrol', { patrolId: {{ $p->id }} })"
                                            class="p-1.5 rounded-lg text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-950"
                                            title="Iniciar Procedimiento desde este Patrullaje">
                                        <i class="fa-solid fa-shield-virus"></i>
                                    </button>

                                    {{-- Finalizar Patrullaje --}}
                                    <button type="button"
                                            wire:click="completePatrol({{ $p->id }})"
                                            wire:confirm="¿Desea marcar como Completado el patrullaje {{ $p->folio }}?"
                                            class="p-1.5 rounded-lg text-ui-muted hover:text-blue-600 hover:bg-ui-subtle"
                                            title="Finalizar Patrullaje">
                                        <i class="fa-solid fa-flag-checkered"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-10 text-ui-muted">
                                <i class="fa-solid fa-shield-halved text-3xl mb-2 text-ui-muted/50 block"></i>
                                <span>No se encontraron patrullajes preventivos registrados con los filtros aplicados.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($patrols->hasPages())
            <div class="p-4 border-t border-ui-border">
                {{ $patrols->links() }}
            </div>
        @endif
    </div>

</div>
