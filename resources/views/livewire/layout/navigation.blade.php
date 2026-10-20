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
     class="bg-slate-900 border-b border-slate-800 text-white shadow-md relative z-40">
    <!-- Primary Navigation Menu -->
    <div class="max-w-[1600px] mx-auto px-3 sm:px-6">
        <div class="flex items-center justify-between h-14 gap-2">
            <!-- Left Side: Brand + Main Navigation & Grouped Dropdowns -->
            <div class="flex items-center gap-1.5 min-w-0">
                <!-- Logo / Brand CAD -->
                <div class="shrink-0 flex items-center pr-3 mr-1 border-r border-slate-800">
                    <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2.5 group">
                        <span class="bg-blue-600 group-hover:bg-blue-500 text-white font-black w-8 h-8 rounded-lg flex items-center justify-center text-sm shadow transition">
                            <i class="fa-solid fa-shield-halved"></i>
                        </span>
                        <div class="leading-tight">
                            <span class="font-black text-sm tracking-wider text-white block">BUINSEGURO</span>
                            <span class="text-[10px] text-slate-400 uppercase font-bold block">Central CAD</span>
                        </div>
                    </a>
                </div>

                <!-- Desktop Navigation Items (Grouped, No Scrollbar) -->
                <div class="hidden md:flex items-center gap-1">
                    @php
                        $navClass = fn($active) => $active
                            ? 'bg-blue-600 text-white font-bold px-3 py-1.5 rounded-lg text-xs inline-flex items-center gap-1.5 shadow-sm transition whitespace-nowrap'
                            : 'text-slate-300 hover:bg-slate-800 hover:text-white font-semibold px-3 py-1.5 rounded-lg text-xs inline-flex items-center gap-1.5 transition whitespace-nowrap';

                        $opsActive = request()->routeIs('field.inspector') || request()->routeIs('events.manage') || request()->routeIs('patrols.manage');
                        $reportsActive = request()->routeIs('reports.calls') || request()->routeIs('reports.index');
                        $adminActive = request()->routeIs('catalogs.manage') || request()->routeIs('landmarks.manage') || request()->routeIs('users.manage');
                    @endphp

                    <!-- 1. Sala de Despacho (Directo) -->
                    <a href="{{ route('dashboard') }}" wire:navigate class="{{ $navClass(request()->routeIs('dashboard')) }}">
                        <i class="fa-solid fa-satellite-dish text-[11px]"></i>
                        <span>Sala Despacho</span>
                    </a>

                    <!-- 2. Mapa en Vivo (Directo) -->
                    <a href="{{ route('map.live') }}" class="{{ $navClass(request()->routeIs('map.live')) }}">
                        <i class="fa-solid fa-map-location-dot text-[11px]"></i>
                        <span>Mapa Vivo</span>
                    </a>

                    <!-- 3. Entrega de Turno (Directo para Operadores/Supervisores/Admin) -->
                    @hasanyrole('Administrador|Supervisor|Operador')
                    <a href="{{ route('shifts.manage') }}" class="{{ $navClass(request()->routeIs('shifts.manage')) }}">
                        <i class="fa-solid fa-clipboard-check text-[11px]"></i>
                        <span>Turno de Sala</span>
                    </a>
                    @endhasanyrole

                    <!-- 4. Dropdown: Operaciones -->
                    <div class="relative" x-data="{ ddOps: false }" @click.outside="ddOps = false">
                        <button type="button"
                                @click="ddOps = !ddOps"
                                class="{{ $navClass($opsActive) }}">
                            <i class="fa-solid fa-layer-group text-[11px]"></i>
                            <span>Operaciones</span>
                            <i class="fa-solid fa-chevron-down text-[9px] opacity-75 transition-transform" :class="ddOps ? 'rotate-180' : ''"></i>
                        </button>

                        <div x-show="ddOps"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             style="display: none;"
                             class="absolute left-0 mt-1.5 w-60 rounded-xl bg-slate-800 border border-slate-700 shadow-xl py-1.5 z-50">
                            <a href="{{ route('field.inspector') }}"
                               class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold {{ request()->routeIs('field.inspector') ? 'bg-blue-600/20 text-blue-300' : 'text-slate-200 hover:bg-slate-700/80 hover:text-white' }}">
                                <i class="fa-solid fa-mobile-screen-button w-4 text-center text-blue-400"></i>
                                <div>
                                    <div class="font-bold">Modo Terreno</div>
                                    <div class="text-[10px] text-slate-400">Panel móvil para inspectores</div>
                                </div>
                            </a>

                            @hasanyrole('Administrador|Supervisor|Operador')
                            <a href="{{ route('events.manage') }}"
                               class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold {{ request()->routeIs('events.manage') ? 'bg-blue-600/20 text-blue-300' : 'text-slate-200 hover:bg-slate-700/80 hover:text-white' }}">
                                <i class="fa-solid fa-cloud-showers-heavy w-4 text-center text-amber-400"></i>
                                <div>
                                    <div class="font-bold">Contingencias y Eventos</div>
                                    <div class="text-[10px] text-slate-400">Procedimientos masivos</div>
                                </div>
                            </a>
                            @endhasanyrole

                            @hasanyrole('Administrador|Supervisor')
                            <a href="{{ route('patrols.manage') }}"
                               class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold {{ request()->routeIs('patrols.manage') ? 'bg-blue-600/20 text-blue-300' : 'text-slate-200 hover:bg-slate-700/80 hover:text-white' }}">
                                <i class="fa-solid fa-car-on w-4 text-center text-emerald-400"></i>
                                <div>
                                    <div class="font-bold">Turnos de Flota / Patrullas</div>
                                    <div class="text-[10px] text-slate-400">Tripulaciones y móviles</div>
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
                                class="{{ $navClass($reportsActive) }}">
                            <i class="fa-solid fa-chart-pie text-[11px]"></i>
                            <span>Reportes y PBX</span>
                            <i class="fa-solid fa-chevron-down text-[9px] opacity-75 transition-transform" :class="ddRep ? 'rotate-180' : ''"></i>
                        </button>

                        <div x-show="ddRep"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             style="display: none;"
                             class="absolute left-0 mt-1.5 w-60 rounded-xl bg-slate-800 border border-slate-700 shadow-xl py-1.5 z-50">
                            <a href="{{ route('reports.calls') }}"
                               class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold {{ request()->routeIs('reports.calls') ? 'bg-blue-600/20 text-blue-300' : 'text-slate-200 hover:bg-slate-700/80 hover:text-white' }}">
                                <i class="fa-solid fa-phone-volume w-4 text-center text-indigo-400"></i>
                                <div>
                                    <div class="font-bold">Historial Llamadas PBX</div>
                                    <div class="text-[10px] text-slate-400">Registro telefónico y audios</div>
                                </div>
                            </a>

                            @hasanyrole('Administrador|Supervisor')
                            <a href="{{ route('reports.index') }}"
                               class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold {{ request()->routeIs('reports.index') ? 'bg-blue-600/20 text-blue-300' : 'text-slate-200 hover:bg-slate-700/80 hover:text-white' }}">
                                <i class="fa-solid fa-chart-column w-4 text-center text-emerald-400"></i>
                                <div>
                                    <div class="font-bold">Estadísticas y Heatmap</div>
                                    <div class="text-[10px] text-slate-400">Métricas SLA y exportación</div>
                                </div>
                            </a>
                            <a href="{{ route('audit.logs') }}"
                               class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold {{ request()->routeIs('audit.logs') ? 'bg-blue-600/20 text-blue-300' : 'text-slate-200 hover:bg-slate-700/80 hover:text-white' }}">
                                <i class="fa-solid fa-shield-halved w-4 text-center text-cyan-400"></i>
                                <div>
                                    <div class="font-bold">Auditoría y Seguridad</div>
                                    <div class="text-[10px] text-slate-400">Bitácora de accesos y trazabilidad</div>
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
                                class="{{ $navClass($adminActive) }}">
                            <i class="fa-solid fa-sliders text-[11px]"></i>
                            <span>Administración</span>
                            <i class="fa-solid fa-chevron-down text-[9px] opacity-75 transition-transform" :class="ddAdm ? 'rotate-180' : ''"></i>
                        </button>

                        <div x-show="ddAdm"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             style="display: none;"
                             class="absolute left-0 mt-1.5 w-60 rounded-xl bg-slate-800 border border-slate-700 shadow-xl py-1.5 z-50">
                            <a href="{{ route('catalogs.manage') }}"
                               class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold {{ request()->routeIs('catalogs.manage') ? 'bg-blue-600/20 text-blue-300' : 'text-slate-200 hover:bg-slate-700/80 hover:text-white' }}">
                                <i class="fa-solid fa-tags w-4 text-center text-amber-400"></i>
                                <div>
                                    <div class="font-bold">Mantenedores y Catálogos</div>
                                    <div class="text-[10px] text-slate-400">Tipificaciones, metadatos y flota</div>
                                </div>
                            </a>

                            <a href="{{ route('landmarks.manage') }}"
                               class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold {{ request()->routeIs('landmarks.manage') ? 'bg-blue-600/20 text-blue-300' : 'text-slate-200 hover:bg-slate-700/80 hover:text-white' }}">
                                <i class="fa-solid fa-location-dot w-4 text-center text-rose-400"></i>
                                <div>
                                    <div class="font-bold">Hitos Geográficos (POI)</div>
                                    <div class="text-[10px] text-slate-400">Puntos de referencia en mapa</div>
                                </div>
                            </a>

                            <a href="{{ route('users.manage') }}"
                               class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold {{ request()->routeIs('users.manage') ? 'bg-blue-600/20 text-blue-300' : 'text-slate-200 hover:bg-slate-700/80 hover:text-white' }}">
                                <i class="fa-solid fa-users-gear w-4 text-center text-sky-400"></i>
                                <div>
                                    <div class="font-bold">Usuarios y Anexos</div>
                                    <div class="text-[10px] text-slate-400">Roles, sectores y extensiones</div>
                                </div>
                            </a>

                            <a href="{{ route('audit.logs') }}"
                               class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold {{ request()->routeIs('audit.logs') ? 'bg-blue-600/20 text-blue-300' : 'text-slate-200 hover:bg-slate-700/80 hover:text-white' }}">
                                <i class="fa-solid fa-shield-halved w-4 text-center text-cyan-400"></i>
                                <div>
                                    <div class="font-bold">Auditoría del Sistema</div>
                                    <div class="text-[10px] text-slate-400">Trazabilidad y seguridad CAD</div>
                                </div>
                            </a>
                        </div>
                    </div>
                    @endrole
                </div>
            </div>

            <!-- Right CAD Status Bar: Clock, Extension & User Dropdown -->
            <div class="hidden sm:flex sm:items-center gap-2 shrink-0">
                <!-- Live Digital Clock -->
                <div class="bg-slate-950 border border-slate-800 px-2.5 py-1 rounded-lg flex items-center gap-1.5">
                    <i class="fa-regular fa-clock text-emerald-400 text-xs"></i>
                    <span x-text="clock" class="font-mono text-xs font-black text-emerald-400 tracking-wider"></span>
                </div>

                <!-- PBX Extension Badge -->
                @if(auth()->user()->extension)
                    <span class="bg-indigo-950/90 border border-indigo-700/80 text-indigo-200 text-xs font-bold px-2.5 py-1 rounded-lg inline-flex items-center gap-1.5">
                        <i class="fa-solid fa-headset text-indigo-400 text-[11px]"></i>
                        <span>Anexo {{ auth()->user()->extension }}</span>
                    </span>
                @endif

                <!-- User Profile Dropdown -->
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 px-2.5 py-1 border border-slate-700 text-xs font-bold rounded-lg text-slate-200 bg-slate-800 hover:bg-slate-700 hover:text-white focus:outline-none transition">
                            <div class="w-6 h-6 rounded-full bg-blue-600/30 border border-blue-500/40 flex items-center justify-center text-blue-300 text-[11px]">
                                <i class="fa-solid fa-user"></i>
                            </div>
                            <div class="text-left leading-tight">
                                <div x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name" class="text-white font-bold text-xs truncate max-w-[130px]"></div>
                                <div class="text-[9px] text-blue-400 uppercase font-extrabold">{{ auth()->user()->roles->pluck('name')->first() ?? 'Operador' }}</div>
                            </div>
                            <i class="fa-solid fa-chevron-down text-[9px] text-slate-400"></i>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile')" wire:navigate>
                            <i class="fa-solid fa-id-badge mr-1.5 text-slate-500"></i> {{ __('Mi Perfil') }}
                        </x-dropdown-link>

                        @hasanyrole('Administrador|Supervisor|Operador')
                        <x-dropdown-link :href="route('shifts.manage')">
                            <i class="fa-solid fa-clipboard-check mr-1.5 text-blue-600"></i> {{ __('Entrega de Turno') }}
                        </x-dropdown-link>
                        @endhasanyrole

                        <x-dropdown-link :href="route('system.docs')">
                            <i class="fa-solid fa-book-open mr-1.5 text-indigo-600"></i> {{ __('Manual del Sistema') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <button wire:click="logout" class="w-full text-start">
                            <x-dropdown-link>
                                <i class="fa-solid fa-right-from-bracket mr-1.5 text-rose-600"></i> {{ __('Cerrar Sesión') }}
                            </x-dropdown-link>
                        </button>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger (Mobile) -->
            <div class="-me-2 flex items-center md:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-slate-400 hover:text-white hover:bg-slate-800 focus:outline-none transition">
                    <i class="fa-solid" :class="open ? 'fa-xmark text-lg' : 'fa-bars text-lg'"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu (Mobile) -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden md:hidden bg-slate-800 border-t border-slate-700">
        <div class="pt-2 pb-3 space-y-1 px-3">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold text-white hover:bg-slate-700">
                <i class="fa-solid fa-satellite-dish w-4 text-center text-blue-400"></i> Sala de Despacho
            </a>
            <a href="{{ route('map.live') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold text-white hover:bg-slate-700">
                <i class="fa-solid fa-map-location-dot w-4 text-center text-emerald-400"></i> Mapa en Vivo
            </a>
            @hasanyrole('Administrador|Supervisor|Operador')
            <a href="{{ route('shifts.manage') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold text-white hover:bg-slate-700">
                <i class="fa-solid fa-clipboard-check w-4 text-center text-amber-400"></i> Entrega de Turno
            </a>
            @endhasanyrole
            <a href="{{ route('field.inspector') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold text-white hover:bg-slate-700">
                <i class="fa-solid fa-mobile-screen-button w-4 text-center text-sky-400"></i> Modo Terreno
            </a>
            @hasanyrole('Administrador|Supervisor|Operador')
            <a href="{{ route('events.manage') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold text-white hover:bg-slate-700">
                <i class="fa-solid fa-cloud-showers-heavy w-4 text-center text-amber-400"></i> Contingencias y Eventos
            </a>
            <a href="{{ route('reports.calls') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold text-white hover:bg-slate-700">
                <i class="fa-solid fa-phone-volume w-4 text-center text-indigo-400"></i> Llamadas PBX
            </a>
            @endhasanyrole
            @hasanyrole('Administrador|Supervisor')
            <a href="{{ route('patrols.manage') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold text-white hover:bg-slate-700">
                <i class="fa-solid fa-car-on w-4 text-center text-emerald-400"></i> Turnos de Flota
            </a>
            <a href="{{ route('reports.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold text-white hover:bg-slate-700">
                <i class="fa-solid fa-chart-column w-4 text-center text-emerald-400"></i> Estadísticas
            </a>
            @endhasanyrole
            @role('Administrador')
            <a href="{{ route('catalogs.manage') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold text-white hover:bg-slate-700">
                <i class="fa-solid fa-tags w-4 text-center text-amber-400"></i> Mantenedores
            </a>
            <a href="{{ route('landmarks.manage') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold text-white hover:bg-slate-700">
                <i class="fa-solid fa-location-dot w-4 text-center text-rose-400"></i> Hitos (POI)
            </a>
            <a href="{{ route('users.manage') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold text-white hover:bg-slate-700">
                <i class="fa-solid fa-users-gear w-4 text-center text-sky-400"></i> Usuarios
            </a>
            @endrole
        </div>

        <div class="pt-3 pb-3 border-t border-slate-700 px-4 flex justify-between items-center">
            <div>
                <div class="font-bold text-sm text-white">{{ auth()->user()->name }}</div>
                <div class="text-xs text-slate-400">{{ auth()->user()->email }}</div>
            </div>
            <button wire:click="logout" class="bg-rose-600 text-white text-xs font-bold px-3 py-1.5 rounded-lg inline-flex items-center gap-1.5">
                <i class="fa-solid fa-right-from-bracket"></i> Salir
            </button>
        </div>
    </div>
</nav>
