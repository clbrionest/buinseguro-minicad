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
                <!-- Logo / Brand CAD (#2C2E83 Cobalt + #94C11E Lime) -->
                <div class="shrink-0 flex items-center pr-3 mr-1 border-r border-white/15">
                    <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2.5 group">
                        <span class="bg-brand-lime group-hover:bg-brand-lime-mid text-brand-black font-black w-8 h-8 rounded-lg flex items-center justify-center text-sm shadow-xs transition">
                            <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                        </span>
                        <div class="leading-tight">
                            <span class="font-black text-sm tracking-wider text-brand-white block">BUIN SEGURO</span>
                            <span class="text-[10px] text-brand-lime-light uppercase font-bold block">Central CAD</span>
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

                        $opsActive = request()->routeIs('field.inspector') || request()->routeIs('events.manage') || request()->routeIs('patrols.manage');
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
                        <span>Mapa Vivo</span>
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

                    <!-- 5. Dropdown: Reportes y PBX -->
                    @hasanyrole('Administrador|Supervisor|Operador')
                    <div class="relative" x-data="{ ddRep: false }" @click.outside="ddRep = false">
                        <button type="button"
                                @click="ddRep = !ddRep"
                                :aria-expanded="ddRep"
                                class="{{ $navClass($reportsActive) }}">
                            <i class="fa-solid fa-chart-pie text-[11px]" aria-hidden="true"></i>
                            <span>Reportes y PBX</span>
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
                                    <div class="font-bold">Historial Llamadas PBX</div>
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
                                    <div class="font-bold">Hitos Geográficos (POI)</div>
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
                                    <div class="text-[10px] text-ui-muted">Trazabilidad y seguridad CAD</div>
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

                <!-- PBX Extension Badge -->
                @if(auth()->user()->extension)
                    <span class="bg-white/10 border border-white/15 text-brand-white text-xs font-bold px-2.5 py-1 rounded-lg inline-flex items-center gap-1.5 font-mono cad-tabular">
                        <i class="fa-solid fa-headset text-brand-lime text-[11px]" aria-hidden="true"></i>
                        <span>Anexo {{ auth()->user()->extension }}</span>
                    </span>
                @endif

                <!-- User Profile Dropdown -->
                <x-dropdown align="right" width="48">
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
                <i class="fa-solid fa-map-location-dot w-4 text-center" aria-hidden="true"></i> Mapa en Vivo
            </a>
            @hasanyrole('Administrador|Supervisor|Operador')
            <a href="{{ route('shifts.manage') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold {{ request()->routeIs('shifts.manage') ? 'bg-brand-lime text-brand-black' : 'text-brand-white hover:bg-white/10' }}">
                <i class="fa-solid fa-clipboard-check w-4 text-center" aria-hidden="true"></i> Entrega de Turno
            </a>
            @endhasanyrole
            <a href="{{ route('field.inspector') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold {{ request()->routeIs('field.inspector') ? 'bg-brand-lime text-brand-black' : 'text-brand-white hover:bg-white/10' }}">
                <i class="fa-solid fa-mobile-screen-button w-4 text-center" aria-hidden="true"></i> Modo Terreno
            </a>
            @hasanyrole('Administrador|Supervisor|Operador')
            <a href="{{ route('events.manage') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold {{ request()->routeIs('events.manage') ? 'bg-brand-lime text-brand-black' : 'text-brand-white hover:bg-white/10' }}">
                <i class="fa-solid fa-cloud-showers-heavy w-4 text-center" aria-hidden="true"></i> Contingencias y Eventos
            </a>
            <a href="{{ route('reports.calls') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold {{ request()->routeIs('reports.calls') ? 'bg-brand-lime text-brand-black' : 'text-brand-white hover:bg-white/10' }}">
                <i class="fa-solid fa-phone-volume w-4 text-center" aria-hidden="true"></i> Llamadas PBX
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
                <i class="fa-solid fa-location-dot w-4 text-center" aria-hidden="true"></i> Hitos (POI)
            </a>
            <a href="{{ route('users.manage') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold {{ request()->routeIs('users.manage') ? 'bg-brand-lime text-brand-black' : 'text-brand-white hover:bg-white/10' }}">
                <i class="fa-solid fa-users-gear w-4 text-center" aria-hidden="true"></i> Usuarios
            </a>
            @endrole
        </div>

        <div class="pt-3 pb-3 border-t border-white/15 px-4 flex justify-between items-center">
            <div>
                <div class="font-bold text-sm text-brand-white">{{ auth()->user()->name }}</div>
                <div class="text-xs text-brand-cobalt-light">{{ auth()->user()->email }}</div>
            </div>
            <button wire:click="logout" class="bg-priority-critical text-brand-white text-xs font-bold px-3 py-1.5 rounded-lg inline-flex items-center gap-1.5">
                <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i> Salir
            </button>
        </div>
    </div>
</nav>
