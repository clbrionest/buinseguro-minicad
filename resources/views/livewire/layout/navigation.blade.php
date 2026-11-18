<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<nav x-data="{
        open: false,
        showChangelogModal: false,
        clock: '',
        initClock() {
            const update = () => {
                const now = new Date();
                this.clock = now.toLocaleTimeString('es-CL', { hour12: false });
            };
            update();
            setInterval(update, 1000);
        }
     }"
     x-init="initClock()"
     aria-label="Navegación Principal Buin Seguro CAD"
     class="bg-ui-sidebar border-b border-white/10 text-brand-white shadow-sm relative z-40">
    <!-- Primary Navigation Menu -->
    <div class="max-w-[1600px] mx-auto px-3 sm:px-6">
        <div class="flex items-center justify-between h-14 gap-2">
            <!-- Left Side: Brand + Main Navigation & Grouped Dropdowns -->
            <div class="flex items-center gap-1.5 min-w-0">
                <!-- Logo / Brand CAD (Escudo Institucional + #94C11E Lime) -->
                <div class="shrink-0 flex items-center pr-3 mr-1 border-r border-white/15">
                    <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2.5 group">
                        <div class="w-9 h-9 rounded-lg bg-white p-1 flex items-center justify-center shadow-xs group-hover:scale-105 transition shrink-0 border border-white/20">
                            <img src="{{ asset('escudo.svg') }}" alt="Escudo Buin Seguro" class="h-full w-auto object-contain">
                        </div>
                        <div class="leading-tight">
                            <span class="font-black text-sm tracking-wider text-brand-white block">BUIN SEGURO</span>
                            <span class="text-[10px] text-brand-lime-light uppercase font-bold block">MiniCAD <span class="font-mono text-[9px] opacity-90">{{ $cadVersionTag ?? 'v1.3.0' }}</span></span>
                        </div>
                    </a>
                </div>

                <!-- Desktop Navigation Items (Grouped, No Scrollbar) -->
                <div class="hidden md:flex items-center gap-1">
                    @php
                        // Accesibilidad §1: sobre lima (#94C11E) usar texto negro (#08080A) de alto contraste
                        $navClass = fn($active) => $active
                            ? 'bg-brand-lime text-brand-black font-extrabold px-3 py-1.5 rounded-lg text-xs inline-flex items-center gap-1.5 shadow-xs transition whitespace-nowrap'
                            : 'text-brand-white/85 hover:bg-white/10 hover:text-brand-white font-semibold px-3 py-1.5 rounded-lg text-xs inline-flex items-center gap-1.5 transition whitespace-nowrap';

                        $opsActive = request()->routeIs('field.inspector') || request()->routeIs('events.manage') || request()->routeIs('patrols.manage') || request()->routeIs('patrols.preventive*');
                        $reportsActive = request()->routeIs('reports.calls') || request()->routeIs('reports.index') || request()->routeIs('audit.logs');
                        $adminActive = request()->routeIs('catalogs.manage') || request()->routeIs('landmarks.manage') || request()->routeIs('users.manage');
                    @endphp

                    <!-- 1. Sala de Despacho (Directo) -->
                    <a href="{{ route('dashboard') }}" wire:navigate class="{{ $navClass(request()->routeIs('dashboard')) }}">
                        <i class="fa-solid fa-satellite-dish text-[11px]" aria-hidden="true"></i>
                        <span>Sala Despacho</span>
                    </a>

                    <!-- 2. Mapa en Vivo (Directo) -->
                    <a href="{{ route('map.live') }}" class="{{ $navClass(request()->routeIs('map.live')) }}">
                        <i class="fa-solid fa-map-location-dot text-[11px]" aria-hidden="true"></i>
                        <span>Mapa Operacional</span>
                    </a>

                    <!-- 3. Entrega de Turno (Directo para Operadores/Supervisores/Admin) -->
                    @hasanyrole('Administrador|Supervisor|Operador')
                    <a href="{{ route('shifts.manage') }}" class="{{ $navClass(request()->routeIs('shifts.manage')) }}">
                        <i class="fa-solid fa-clipboard-check text-[11px]" aria-hidden="true"></i>
                        <span>Turno de Sala</span>
                    </a>
                    @endhasanyrole

                    <!-- 4. Dropdown: Operaciones -->
                    <div class="relative" x-data="{ ddOps: false }" @click.outside="ddOps = false">
                        <button type="button"
                                @click="ddOps = !ddOps"
                                :aria-expanded="ddOps"
                                class="{{ $navClass($opsActive) }}">
                            <i class="fa-solid fa-layer-group text-[11px]" aria-hidden="true"></i>
                            <span>Operaciones</span>
                            <i class="fa-solid fa-chevron-down text-[9px] opacity-75 transition-transform" :class="ddOps ? 'rotate-180' : ''" aria-hidden="true"></i>
                        </button>

                        <div x-show="ddOps"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             style="display: none;"
                             class="absolute left-0 mt-1.5 w-64 rounded-xl bg-ui-card text-ui-fg border border-ui-border shadow-xl py-1.5 z-50">
                            <a href="{{ route('patrols.preventive') }}"
                               class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold {{ request()->routeIs('patrols.preventive*') ? 'bg-brand-cobalt/10 text-brand-cobalt dark:bg-brand-cobalt-mid/20 dark:text-brand-cobalt-light' : 'text-ui-fg hover:bg-ui-subtle' }}">
                                <i class="fa-solid fa-shield-halved w-4 text-center text-brand-lime" aria-hidden="true"></i>
                                <div>
                                    <div class="font-bold">Patrullajes Preventivos</div>
                                    <div class="text-[10px] text-ui-muted">Rondas, hitos y control de ruta</div>
                                </div>
                            </a>

                            <a href="{{ route('field.inspector') }}"
                               class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold {{ request()->routeIs('field.inspector') ? 'bg-brand-cobalt/10 text-brand-cobalt dark:bg-brand-cobalt-mid/20 dark:text-brand-cobalt-light' : 'text-ui-fg hover:bg-ui-subtle' }}">
                                <i class="fa-solid fa-mobile-screen-button w-4 text-center text-brand-cobalt dark:text-brand-cobalt-mid" aria-hidden="true"></i>
                                <div>
                                    <div class="font-bold">Modo Terreno</div>
                                    <div class="text-[10px] text-ui-muted">Panel móvil para inspectores</div>
                                </div>
                            </a>

                            @hasanyrole('Administrador|Supervisor|Operador')
                            <a href="{{ route('events.manage') }}"
                               class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold {{ request()->routeIs('events.manage') ? 'bg-brand-cobalt/10 text-brand-cobalt dark:bg-brand-cobalt-mid/20 dark:text-brand-cobalt-light' : 'text-ui-fg hover:bg-ui-subtle' }}">
                                <i class="fa-solid fa-cloud-showers-heavy w-4 text-center text-priority-high" aria-hidden="true"></i>
                                <div>
                                    <div class="font-bold">Contingencias y Eventos</div>
                                    <div class="text-[10px] text-ui-muted">Procedimientos masivos</div>
                                </div>
                            </a>
                            @endhasanyrole

                            @hasanyrole('Administrador|Supervisor')
                            <a href="{{ route('patrols.manage') }}"
                               class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold {{ request()->routeIs('patrols.manage') ? 'bg-brand-cobalt/10 text-brand-cobalt dark:bg-brand-cobalt-mid/20 dark:text-brand-cobalt-light' : 'text-ui-fg hover:bg-ui-subtle' }}">
                                <i class="fa-solid fa-car-on w-4 text-center text-state-completed" aria-hidden="true"></i>
                                <div>
                                    <div class="font-bold">Turnos de Flota / Patrullas</div>
                                    <div class="text-[10px] text-ui-muted">Tripulaciones y móviles</div>
                                </div>
                            </a>
                            @endhasanyrole
                        </div>
                    </div>

                    <!-- 5. Dropdown: Reportes e Historial Telefónico -->
                    @hasanyrole('Administrador|Supervisor|Operador')
                    <div class="relative" x-data="{ ddRep: false }" @click.outside="ddRep = false">
                        <button type="button"
                                @click="ddRep = !ddRep"
                                :aria-expanded="ddRep"
                                class="{{ $navClass($reportsActive) }}">
                            <i class="fa-solid fa-chart-pie text-[11px]" aria-hidden="true"></i>
                            <span>Reportes</span>
                            <i class="fa-solid fa-chevron-down text-[9px] opacity-75 transition-transform" :class="ddRep ? 'rotate-180' : ''" aria-hidden="true"></i>
                        </button>

                        <div x-show="ddRep"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             style="display: none;"
                             class="absolute left-0 mt-1.5 w-64 rounded-xl bg-ui-card text-ui-fg border border-ui-border shadow-xl py-1.5 z-50">
                            <a href="{{ route('reports.calls') }}"
                               class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold {{ request()->routeIs('reports.calls') ? 'bg-brand-cobalt/10 text-brand-cobalt dark:bg-brand-cobalt-mid/20 dark:text-brand-cobalt-light' : 'text-ui-fg hover:bg-ui-subtle' }}">
                                <i class="fa-solid fa-phone-volume w-4 text-center text-brand-cobalt dark:text-brand-cobalt-mid" aria-hidden="true"></i>
                                <div>
                                    <div class="font-bold">Historial Llamadas</div>
                                    <div class="text-[10px] text-ui-muted">Registro telefónico y audios</div>
                                </div>
                            </a>

                            @hasanyrole('Administrador|Supervisor')
                            <a href="{{ route('reports.index') }}"
                               class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold {{ request()->routeIs('reports.index') ? 'bg-brand-cobalt/10 text-brand-cobalt dark:bg-brand-cobalt-mid/20 dark:text-brand-cobalt-light' : 'text-ui-fg hover:bg-ui-subtle' }}">
                                <i class="fa-solid fa-chart-column w-4 text-center text-state-completed" aria-hidden="true"></i>
                                <div>
                                    <div class="font-bold">Estadísticas y Heatmap</div>
                                    <div class="text-[10px] text-ui-muted">Métricas SLA y exportación</div>
                                </div>
                            </a>
                            <a href="{{ route('audit.logs') }}"
                               class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold {{ request()->routeIs('audit.logs') ? 'bg-brand-cobalt/10 text-brand-cobalt dark:bg-brand-cobalt-mid/20 dark:text-brand-cobalt-light' : 'text-ui-fg hover:bg-ui-subtle' }}">
                                <i class="fa-solid fa-shield-halved w-4 text-center text-state-progress" aria-hidden="true"></i>
                                <div>
                                    <div class="font-bold">Auditoría y Seguridad</div>
                                    <div class="text-[10px] text-ui-muted">Bitácora de accesos y trazabilidad</div>
                                </div>
                            </a>
                            @endhasanyrole
                        </div>
                    </div>
                    @endhasanyrole

                    <!-- 6. Dropdown: Administración -->
                    @role('Administrador')
                    <div class="relative" x-data="{ ddAdm: false }" @click.outside="ddAdm = false">
                        <button type="button"
                                @click="ddAdm = !ddAdm"
                                :aria-expanded="ddAdm"
                                class="{{ $navClass($adminActive) }}">
                            <i class="fa-solid fa-sliders text-[11px]" aria-hidden="true"></i>
                            <span>Administración</span>
                            <i class="fa-solid fa-chevron-down text-[9px] opacity-75 transition-transform" :class="ddAdm ? 'rotate-180' : ''" aria-hidden="true"></i>
                        </button>

                        <div x-show="ddAdm"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             style="display: none;"
                             class="absolute left-0 mt-1.5 w-64 rounded-xl bg-ui-card text-ui-fg border border-ui-border shadow-xl py-1.5 z-50">
                            <a href="{{ route('catalogs.manage') }}"
                               class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold {{ request()->routeIs('catalogs.manage') ? 'bg-brand-cobalt/10 text-brand-cobalt dark:bg-brand-cobalt-mid/20 dark:text-brand-cobalt-light' : 'text-ui-fg hover:bg-ui-subtle' }}">
                                <i class="fa-solid fa-tags w-4 text-center text-priority-high" aria-hidden="true"></i>
                                <div>
                                    <div class="font-bold">Mantenedores y Catálogos</div>
                                    <div class="text-[10px] text-ui-muted">Tipificaciones, metadatos y flota</div>
                                </div>
                            </a>

                            <a href="{{ route('landmarks.manage') }}"
                               class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold {{ request()->routeIs('landmarks.manage') ? 'bg-brand-cobalt/10 text-brand-cobalt dark:bg-brand-cobalt-mid/20 dark:text-brand-cobalt-light' : 'text-ui-fg hover:bg-ui-subtle' }}">
                                <i class="fa-solid fa-location-dot w-4 text-center text-priority-critical" aria-hidden="true"></i>
                                <div>
                                    <div class="font-bold">Hitos y Referencias</div>
                                    <div class="text-[10px] text-ui-muted">Puntos de referencia en mapa</div>
                                </div>
                            </a>

                            <a href="{{ route('users.manage') }}"
                               class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold {{ request()->routeIs('users.manage') ? 'bg-brand-cobalt/10 text-brand-cobalt dark:bg-brand-cobalt-mid/20 dark:text-brand-cobalt-light' : 'text-ui-fg hover:bg-ui-subtle' }}">
                                <i class="fa-solid fa-users-gear w-4 text-center text-state-progress" aria-hidden="true"></i>
                                <div>
                                    <div class="font-bold">Usuarios y Anexos</div>
                                    <div class="text-[10px] text-ui-muted">Roles, sectores y extensiones</div>
                                </div>
                            </a>

                            <a href="{{ route('audit.logs') }}"
                               class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold {{ request()->routeIs('audit.logs') ? 'bg-brand-cobalt/10 text-brand-cobalt dark:bg-brand-cobalt-mid/20 dark:text-brand-cobalt-light' : 'text-ui-fg hover:bg-ui-subtle' }}">
                                <i class="fa-solid fa-shield-halved w-4 text-center text-brand-cobalt dark:text-brand-cobalt-mid" aria-hidden="true"></i>
                                <div>
                                    <div class="font-bold">Auditoría del Sistema</div>
                                    <div class="text-[10px] text-ui-muted">Trazabilidad</div>
                                </div>
                            </a>
                        </div>
                    </div>
                    @endrole
                </div>
            </div>

            <!-- Right CAD Status Bar: Theme Switcher, Clock, Extension & User Dropdown -->
            <div class="hidden sm:flex sm:items-center gap-2 shrink-0">
                <!-- Selector de Tema (Claro / Oscuro / Auto) -->
                <x-cad.theme-switcher />

                <!-- Live Digital Clock (Tabular Nums) -->
                <div class="bg-[#1D1F59]/80 dark:bg-[#10131D] border border-white/15 px-2.5 py-1 rounded-lg flex items-center gap-1.5" title="Hora Oficial Central CAD">
                    <i class="fa-regular fa-clock text-brand-lime text-xs" aria-hidden="true"></i>
                    <span x-text="clock" class="font-mono cad-tabular text-xs font-black text-brand-white tracking-wider"></span>
                </div>

                <!-- Extension Badge -->
                @if(auth()->user()->extension)
                    <span class="bg-white/10 border border-white/15 text-brand-white text-xs font-bold px-2.5 py-1 rounded-lg inline-flex items-center gap-1.5 font-mono cad-tabular">
                        <i class="fa-solid fa-headset text-brand-lime text-[11px]" aria-hidden="true"></i>
                        <span>Anexo {{ auth()->user()->extension }}</span>
                    </span>
                @endif

                <!-- User Profile Dropdown -->
                <x-dropdown align="right" width="w-80">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 px-2.5 py-1 border border-white/15 text-xs font-bold rounded-lg text-brand-white bg-white/10 hover:bg-white/15 focus:outline-none transition">
                            <div class="w-6 h-6 rounded-full bg-brand-lime text-brand-black flex items-center justify-center font-black text-[11px]">
                                <i class="fa-solid fa-user" aria-hidden="true"></i>
                            </div>
                            <div class="text-left leading-tight">
                                <div x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name" class="text-brand-white font-bold text-xs truncate max-w-[120px]"></div>
                                <div class="text-[9px] text-brand-lime-light uppercase font-extrabold">{{ auth()->user()->roles->pluck('name')->first() ?? 'Operador' }}</div>
                            </div>
                            <i class="fa-solid fa-chevron-down text-[9px] text-brand-white/70" aria-hidden="true"></i>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <!-- Cabecera del Perfil con Versión SemVer -->
                        <div class="px-4 py-2.5 border-b border-ui-border flex items-center justify-between gap-2 bg-ui-subtle/50">
                            <div class="min-w-0">
                                <div class="text-xs font-extrabold text-ui-fg truncate">{{ auth()->user()->name }}</div>
                                <div class="text-[10px] text-ui-muted truncate">{{ auth()->user()->email }}</div>
                            </div>
                            <span class="bg-brand-cobalt text-brand-white dark:bg-brand-lime dark:text-brand-black font-mono font-black text-[10px] px-2 py-0.5 rounded shrink-0" title="Versión SemVer del Sistema">
                                {{ $cadVersionTag ?? 'v1.3.0' }}
                            </span>
                        </div>

                        <x-dropdown-link :href="route('profile')" wire:navigate>
                            <i class="fa-solid fa-id-badge mr-1.5 text-ui-muted" aria-hidden="true"></i> {{ __('Mi Perfil') }}
                        </x-dropdown-link>

                        @hasanyrole('Administrador|Supervisor|Operador')
                        <x-dropdown-link :href="route('shifts.manage')">
                            <i class="fa-solid fa-clipboard-check mr-1.5 text-brand-cobalt dark:text-brand-cobalt-mid" aria-hidden="true"></i> {{ __('Entrega de Turno') }}
                        </x-dropdown-link>
                        @endhasanyrole

                        <x-dropdown-link :href="route('system.docs')">
                            <i class="fa-solid fa-book-open mr-1.5 text-brand-cobalt dark:text-brand-cobalt-mid" aria-hidden="true"></i> {{ __('Manual del Sistema') }}
                        </x-dropdown-link>

                        <!-- Sección de Versión SemVer y Resumen de Cambios (Sincronizado desde main) -->
                        @if(!empty($cadLatestRelease))
                            <div class="px-3.5 py-2.5 border-t border-b border-ui-border bg-ui-subtle/60 text-left" @click.stop>
                                <div class="flex items-center justify-between gap-1.5 mb-1.5">
                                    <span class="text-[10px] font-black uppercase tracking-wider text-brand-cobalt dark:text-brand-lime flex items-center gap-1">
                                        <i class="fa-solid fa-code-branch" aria-hidden="true"></i>
                                        <span>Versión {{ $cadVersionTag ?? 'v1.3.0' }}</span>
                                    </span>
                                    <span class="text-[9px] font-mono text-ui-muted">{{ $cadLatestRelease['date'] ?? '' }}</span>
                                </div>
                                <div class="text-[11px] font-bold text-ui-fg mb-1.5 leading-snug">
                                    {{ $cadLatestRelease['title'] ?? 'Resumen de Cambios' }}
                                </div>
                                <ul class="space-y-1 text-[10px] text-ui-muted mb-2">
                                    @foreach(array_slice($cadLatestRelease['changes'] ?? [], 0, 3) as $chg)
                                        <li class="flex items-start gap-1 leading-tight">
                                            <span class="text-brand-lime font-black">•</span>
                                            <span class="line-clamp-2">{{ $chg['description'] ?? '' }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                                <button type="button"
                                        @click="showChangelogModal = true"
                                        class="w-full text-center text-[10px] font-extrabold py-1 px-2 rounded bg-brand-cobalt/10 hover:bg-brand-cobalt/20 text-brand-cobalt dark:bg-brand-lime/15 dark:hover:bg-brand-lime/25 dark:text-brand-lime transition">
                                    <i class="fa-solid fa-list-check mr-1" aria-hidden="true"></i>
                                    Ver Changelog completo ({{ count($cadReleases ?? []) }} versiones)
                                </button>
                            </div>
                        @endif

                        <!-- Authentication -->
                        <button wire:click="logout" class="w-full text-start">
                            <x-dropdown-link>
                                <i class="fa-solid fa-right-from-bracket mr-1.5 text-priority-critical" aria-hidden="true"></i> {{ __('Cerrar Sesión') }}
                            </x-dropdown-link>
                        </button>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger (Mobile) -->
            <div class="-me-2 flex items-center gap-2 md:hidden">
                <x-cad.theme-switcher :compact="true" />
                <button @click="open = ! open"
                        aria-label="Abrir menú de navegación"
                        class="inline-flex items-center justify-center p-2 rounded-md text-brand-white/80 hover:text-brand-white hover:bg-white/10 focus:outline-none transition">
                    <i class="fa-solid" :class="open ? 'fa-xmark text-lg' : 'fa-bars text-lg'" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu (Mobile) -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden md:hidden bg-ui-sidebar border-t border-white/15">
        <div class="pt-2 pb-3 space-y-1 px-3">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold {{ request()->routeIs('dashboard') ? 'bg-brand-lime text-brand-black' : 'text-brand-white hover:bg-white/10' }}">
                <i class="fa-solid fa-satellite-dish w-4 text-center" aria-hidden="true"></i> Sala de Despacho
            </a>
            <a href="{{ route('map.live') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold {{ request()->routeIs('map.live') ? 'bg-brand-lime text-brand-black' : 'text-brand-white hover:bg-white/10' }}">
                <i class="fa-solid fa-map-location-dot w-4 text-center" aria-hidden="true"></i> Mapa Operacional
            </a>
            @hasanyrole('Administrador|Supervisor|Operador')
            <a href="{{ route('shifts.manage') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold {{ request()->routeIs('shifts.manage') ? 'bg-brand-lime text-brand-black' : 'text-brand-white hover:bg-white/10' }}">
                <i class="fa-solid fa-clipboard-check w-4 text-center" aria-hidden="true"></i> Entrega de Turno
            </a>
            @endhasanyrole
            <a href="{{ route('patrols.preventive') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold {{ request()->routeIs('patrols.preventive*') ? 'bg-brand-lime text-brand-black' : 'text-brand-white hover:bg-white/10' }}">
                <i class="fa-solid fa-shield-halved w-4 text-center" aria-hidden="true"></i> Patrullajes Preventivos
            </a>
            <a href="{{ route('field.inspector') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold {{ request()->routeIs('field.inspector') ? 'bg-brand-lime text-brand-black' : 'text-brand-white hover:bg-white/10' }}">
                <i class="fa-solid fa-mobile-screen-button w-4 text-center" aria-hidden="true"></i> Modo Terreno
            </a>
            @hasanyrole('Administrador|Supervisor|Operador')
            <a href="{{ route('events.manage') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold {{ request()->routeIs('events.manage') ? 'bg-brand-lime text-brand-black' : 'text-brand-white hover:bg-white/10' }}">
                <i class="fa-solid fa-cloud-showers-heavy w-4 text-center" aria-hidden="true"></i> Contingencias y Eventos
            </a>
            <a href="{{ route('reports.calls') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold {{ request()->routeIs('reports.calls') ? 'bg-brand-lime text-brand-black' : 'text-brand-white hover:bg-white/10' }}">
                <i class="fa-solid fa-phone-volume w-4 text-center" aria-hidden="true"></i> Llamadas
            </a>
            @endhasanyrole
            @hasanyrole('Administrador|Supervisor')
            <a href="{{ route('patrols.manage') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold {{ request()->routeIs('patrols.manage') ? 'bg-brand-lime text-brand-black' : 'text-brand-white hover:bg-white/10' }}">
                <i class="fa-solid fa-car-on w-4 text-center" aria-hidden="true"></i> Turnos de Flota
            </a>
            <a href="{{ route('reports.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold {{ request()->routeIs('reports.index') ? 'bg-brand-lime text-brand-black' : 'text-brand-white hover:bg-white/10' }}">
                <i class="fa-solid fa-chart-column w-4 text-center" aria-hidden="true"></i> Estadísticas
            </a>
            @endhasanyrole
            @role('Administrador')
            <a href="{{ route('catalogs.manage') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold {{ request()->routeIs('catalogs.manage') ? 'bg-brand-lime text-brand-black' : 'text-brand-white hover:bg-white/10' }}">
                <i class="fa-solid fa-tags w-4 text-center" aria-hidden="true"></i> Mantenedores
            </a>
            <a href="{{ route('landmarks.manage') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold {{ request()->routeIs('landmarks.manage') ? 'bg-brand-lime text-brand-black' : 'text-brand-white hover:bg-white/10' }}">
                <i class="fa-solid fa-location-dot w-4 text-center" aria-hidden="true"></i> Hitos
            </a>
            <a href="{{ route('users.manage') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold {{ request()->routeIs('users.manage') ? 'bg-brand-lime text-brand-black' : 'text-brand-white hover:bg-white/10' }}">
                <i class="fa-solid fa-users-gear w-4 text-center" aria-hidden="true"></i> Usuarios
            </a>
            @endrole
        </div>

        <div class="pt-3 pb-3 border-t border-white/15 px-4 space-y-2">
            <div class="flex justify-between items-center">
                <div>
                    <div class="font-bold text-sm text-brand-white flex items-center gap-2">
                        <span>{{ auth()->user()->name }}</span>
                        <span class="bg-brand-lime text-brand-black font-mono font-black text-[10px] px-1.5 py-0.5 rounded">{{ $cadVersionTag ?? 'v1.3.0' }}</span>
                    </div>
                    <div class="text-xs text-brand-cobalt-light">{{ auth()->user()->email }}</div>
                </div>
                <button wire:click="logout" class="bg-priority-critical text-brand-white text-xs font-bold px-3 py-1.5 rounded-lg inline-flex items-center gap-1.5">
                    <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i> Salir
                </button>
            </div>
            <button type="button"
                    @click="showChangelogModal = true; open = false"
                    class="w-full text-left text-[11px] text-brand-lime-light hover:underline flex items-center gap-1.5 pt-1">
                <i class="fa-solid fa-code-branch" aria-hidden="true"></i>
                <span>Ver novedades de la versión {{ $cadVersionTag ?? 'v1.3.0' }} y Changelog</span>
            </button>
        </div>
    </div>

    <!-- Modal de Historial de Versiones SemVer y Changelog (PRs) -->
    <div x-show="showChangelogModal"
         x-transition.opacity
         style="display: none;"
         @keydown.escape.window="showChangelogModal = false"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/80 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-ui-card border border-ui-border text-ui-fg rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden my-8"
             @click.outside="showChangelogModal = false">
            <!-- Header del Modal -->
            <div class="bg-ui-sidebar text-brand-white px-6 py-4 flex items-center justify-between border-b border-white/10">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-brand-lime text-brand-black flex items-center justify-center font-black text-sm">
                        <i class="fa-solid fa-code-branch" aria-hidden="true"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-black tracking-wide uppercase">Registro de Versiones (Changelog)</span>
                            <span class="bg-brand-lime text-brand-black font-mono text-[10px] font-black px-2 py-0.5 rounded">{{ $cadVersionTag ?? 'v1.3.0' }}</span>
                        </div>
                        <p class="text-[11px] text-brand-cobalt-light">Versionado Semántico (SemVer) sincronizado automáticamente desde rama main</p>
                    </div>
                </div>
                <button type="button"
                        @click="showChangelogModal = false"
                        class="text-white/70 hover:text-white p-1.5 rounded-lg hover:bg-white/10 transition">
                    <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i>
                </button>
            </div>

            <!-- Lista de Versiones y PRs -->
            <div class="p-6 max-h-[70vh] overflow-y-auto space-y-4">
                @foreach($cadReleases ?? [] as $idx => $rel)
                    <div class="p-4 rounded-xl border {{ $idx === 0 ? 'bg-brand-cobalt/5 border-brand-cobalt/40 dark:bg-brand-lime/5 dark:border-brand-lime/40' : 'bg-ui-subtle border-ui-border' }}">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-2 border-b border-ui-border pb-2">
                            <div class="flex items-center gap-2">
                                <span class="font-mono font-black text-xs px-2 py-0.5 rounded {{ $idx === 0 ? 'bg-brand-lime text-brand-black' : 'bg-slate-700 text-white' }}">
                                    v{{ $rel['version'] }}
                                </span>
                                <span class="font-extrabold text-sm text-ui-fg">{{ $rel['title'] ?? '' }}</span>
                                @if($idx === 0)
                                    <span class="text-[9px] uppercase font-black bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 px-2 py-0.5 rounded-full">
                                        Actual
                                    </span>
                                @endif
                            </div>
                            <span class="text-xs font-mono text-ui-muted">{{ $rel['date'] }}</span>
                        </div>

                        @if(!empty($rel['pr_refs']))
                            <div class="flex flex-wrap items-center gap-1.5 mb-2.5">
                                <span class="text-[10px] font-bold text-ui-muted uppercase">Referencias / PRs:</span>
                                @foreach($rel['pr_refs'] as $prRef)
                                    <span class="bg-ui-card border border-ui-border px-2 py-0.5 rounded font-mono text-[10px] font-bold text-brand-cobalt dark:text-brand-lime">
                                        {{ $prRef }}
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        <ul class="space-y-1.5 text-xs">
                            @foreach($rel['changes'] ?? [] as $change)
                                <li class="flex items-start gap-2">
                                    <span class="mt-0.5 px-1.5 py-0.2 rounded text-[9px] font-black uppercase shrink-0 {{ ($change['type'] ?? '') === 'feat' ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' : (($change['type'] ?? '') === 'fix' ? 'bg-amber-500/15 text-amber-600 dark:text-amber-400' : 'bg-blue-500/15 text-blue-600 dark:text-blue-400') }}">
                                        {{ strtoupper($change['type'] ?? 'cambio') }}
                                    </span>
                                    <span class="text-ui-fg">{{ $change['description'] ?? '' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>

            <!-- Footer -->
            <div class="px-6 py-3 bg-ui-subtle border-t border-ui-border flex items-center justify-between text-xs text-ui-muted">
                <span>Estándar <strong>Semantic Versioning 2.0.0</strong></span>
                <button type="button"
                        @click="showChangelogModal = false"
                        class="px-4 py-1.5 rounded-lg bg-brand-cobalt text-white font-bold text-xs hover:bg-brand-cobalt-deep transition">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</nav>
