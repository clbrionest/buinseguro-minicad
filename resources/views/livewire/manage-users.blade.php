<div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

    {{-- Notificaciones Flash --}}
    @if (session()->has('message'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-900 text-sm px-4 py-3 rounded-xl flex items-center justify-between shadow-xs" role="status">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-emerald-600" aria-hidden="true"></i>
                <span class="font-semibold">{{ session('message') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-700 hover:text-emerald-900 text-sm font-bold">✕</button>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="bg-red-50 border border-red-200 text-red-900 text-sm px-4 py-3 rounded-xl flex items-center justify-between shadow-xs" role="alert">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation text-red-600" aria-hidden="true"></i>
                <span class="font-semibold">{{ session('error') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-red-700 hover:text-red-900 text-sm font-bold">✕</button>
        </div>
    @endif

    {{-- Encabezado Principal --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fa-solid fa-users-gear text-brand-cobalt dark:text-brand-cobalt-mid" aria-hidden="true"></i>
                <span>Administración de Usuarios y Permisos</span>
            </h2>
            <p class="text-xs text-slate-500 mt-1">
                Gestiona cuentas, roles operativos, cobertura por sectores y envío de credenciales de bienvenida (onboarding).
            </p>
        </div>

        <x-cad.button type="button" variant="accent" size="md" wire:click="openCreateModal">
            <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
            <span>Nuevo Usuario</span>
        </x-cad.button>
    </div>

    {{-- KPIs Rápidos de Dotación (actúan como filtros rápidos por Rol) --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        @php
            $kpiCards = [
                ['key' => '', 'label' => 'Dotación Total', 'count' => $roleCounts['total'], 'icon' => 'fa-users', 'accent' => 'text-slate-700'],
                ['key' => 'Administrador', 'label' => 'Administradores', 'count' => $roleCounts['Administrador'], 'icon' => 'fa-user-shield', 'accent' => 'text-indigo-700'],
                ['key' => 'Supervisor', 'label' => 'Supervisores', 'count' => $roleCounts['Supervisor'], 'icon' => 'fa-user-tie', 'accent' => 'text-amber-700'],
                ['key' => 'Operador', 'label' => 'Operadores', 'count' => $roleCounts['Operador'], 'icon' => 'fa-headset', 'accent' => 'text-blue-700'],
                ['key' => 'Inspector', 'label' => 'Inspectores', 'count' => $roleCounts['Inspector'], 'icon' => 'fa-shield-halved', 'accent' => 'text-emerald-700'],
            ];
        @endphp
        @foreach($kpiCards as $kpi)
            @php $isActiveKpi = $filterRole === $kpi['key']; @endphp
            <button type="button"
                    wire:click="$set('filterRole', '{{ $kpi['key'] }}')"
                    class="text-left bg-white rounded-xl p-3.5 border transition shadow-xs hover:bg-slate-50 {{ $isActiveKpi ? 'border-brand-cobalt ring-2 ring-brand-cobalt/20 dark:border-brand-cobalt-mid' : 'border-slate-200' }}">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">{{ $kpi['label'] }}</span>
                    <i class="fa-solid {{ $kpi['icon'] }} text-xs {{ $kpi['accent'] }}" aria-hidden="true"></i>
                </div>
                <div class="text-2xl font-black text-slate-900 mt-1 cad-tabular">{{ $kpi['count'] }}</div>
            </button>
        @endforeach
    </div>

    {{-- Barra de Búsqueda y Filtros --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4 flex flex-col md:flex-row items-stretch md:items-center gap-3">
        <div class="relative flex-1">
            <input type="text"
                   wire:model.live.debounce.300ms="search"
                   placeholder="Buscar por nombre, correo o anexo telefónico..."
                   class="w-full text-xs rounded-lg border-slate-300 pl-9 pr-3 py-2 shadow-xs focus:ring-brand-cobalt focus:border-brand-cobalt">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400 text-xs" aria-hidden="true"></i>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <select wire:model.live="filterRole" class="text-xs rounded-lg border-slate-300 py-2 pr-8 font-semibold">
                <option value="">Todos los roles</option>
                @foreach($roles as $role)
                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="filterSector" class="text-xs rounded-lg border-slate-300 py-2 pr-8 font-semibold">
                <option value="">Todos los sectores</option>
                @foreach($sectors as $sector)
                    <option value="{{ $sector->id }}">{{ $sector->name }}</option>
                @endforeach
            </select>

            @if($search !== '' || $filterRole !== '' || $filterSector !== '')
                <button type="button"
                        wire:click="clearFilters"
                        class="px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-xs font-bold text-slate-700 transition inline-flex items-center gap-1.5">
                    <i class="fa-solid fa-rotate-left text-[10px]" aria-hidden="true"></i>
                    <span>Limpiar</span>
                </button>
            @endif
        </div>
    </div>

    {{-- Tabla de Usuarios --}}
    <div class="bg-white shadow-xs rounded-2xl border border-slate-200">
        <div class="overflow-x-auto sm:overflow-visible">
            <table class="min-w-full leading-normal">
                <thead>
                    <tr class="bg-slate-900 text-white text-xs uppercase tracking-wider">
                        <th class="py-3 px-4 text-left font-black rounded-tl-2xl">Usuario</th>
                        <th class="py-3 px-4 text-left font-black">Rol</th>
                        <th class="py-3 px-4 text-center font-black">Anexo</th>
                        <th class="py-3 px-4 text-left font-black">Cobertura / Sectores</th>
                        <th class="py-3 px-4 text-center font-black w-32 rounded-tr-2xl">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-xs divide-y divide-slate-100">
                    @forelse ($users as $user)
                        @php
                            $roleName = $user->roles->first()->name ?? 'Sin Rol';
                            $roleBadgeClass = match ($roleName) {
                                'Administrador' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                                'Supervisor'    => 'bg-amber-100 text-amber-800 border-amber-200',
                                'Operador'      => 'bg-blue-100 text-blue-800 border-blue-200',
                                'Inspector'     => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                default         => 'bg-slate-100 text-slate-700 border-slate-200',
                            };
                            $initials = collect(explode(' ', trim($user->name)))
                                ->filter()
                                ->take(2)
                                ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                                ->implode('');
                        @endphp
                        <tr wire:key="user-row-{{ $user->id }}" class="hover:bg-slate-50/80 transition">
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-brand-cobalt/15 text-brand-cobalt dark:bg-brand-cobalt-mid/25 dark:text-brand-cobalt-light font-black text-xs flex items-center justify-center shrink-0">
                                        {{ $initials ?: 'U' }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-slate-900 truncate flex items-center gap-1.5">
                                            <span>{{ $user->name }}</span>
                                            @if(auth()->id() === $user->id)
                                                <span class="text-[9px] font-black uppercase px-1.5 py-0.5 rounded bg-brand-lime text-brand-black">Tú</span>
                                            @endif
                                        </div>
                                        <div class="text-[11px] text-slate-500 truncate font-mono">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>

                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-extrabold border {{ $roleBadgeClass }}">
                                    {{ $roleName }}
                                </span>
                            </td>

                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @if($user->extension)
                                    <span class="inline-flex items-center gap-1 bg-slate-100 px-2.5 py-0.5 rounded-md border border-slate-200 text-slate-700 font-mono font-bold text-[11px]">
                                        <i class="fa-solid fa-phone text-[10px] text-slate-500" aria-hidden="true"></i>
                                        <span>{{ $user->extension }}</span>
                                    </span>
                                @else
                                    <span class="text-[11px] text-slate-400 italic">—</span>
                                @endif
                            </td>

                            <td class="py-3 px-4">
                                @if(in_array($roleName, ['Administrador', 'Supervisor'], true))
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 px-2 py-0.5 rounded-md">
                                        <i class="fa-solid fa-globe text-[10px]" aria-hidden="true"></i>
                                        <span>Acceso global (todos los sectores)</span>
                                    </span>
                                @elseif($roleName === 'Inspector')
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-md">
                                        <i class="fa-solid fa-car-side text-[10px]" aria-hidden="true"></i>
                                        <span>Según móvil asignado en turno</span>
                                    </span>
                                @else
                                    <div class="flex flex-wrap gap-1">
                                        @forelse($user->sectors as $sector)
                                            <span class="bg-slate-100 text-slate-700 border border-slate-200 py-0.5 px-2 rounded text-[11px] font-semibold">{{ $sector->name }}</span>
                                        @empty
                                            <span class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-800 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-md">
                                                <i class="fa-solid fa-triangle-exclamation text-[10px]" aria-hidden="true"></i>
                                                <span>Sin sectores asignados</span>
                                            </span>
                                        @endforelse
                                    </div>
                                @endif
                            </td>

                            <td class="py-3 px-4 text-center whitespace-nowrap w-32">
                                <div class="relative inline-block text-left"
                                     x-data="{ open: false }"
                                     @click.outside="open = false"
                                     @keydown.escape.window="open = false">
                                    <button type="button"
                                            @click="open = !open"
                                            class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-700 shadow-xs hover:bg-slate-50 transition">
                                        <span>Acciones</span>
                                        <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 transition-transform duration-150" :class="{ 'rotate-180': open }"></i>
                                    </button>

                                    <div x-show="open"
                                         x-transition
                                         style="display: none;"
                                         class="absolute right-0 z-50 w-56 rounded-xl bg-white shadow-xl border border-slate-200 py-1.5 {{ $loop->remaining < 2 && $loop->count > 2 ? 'bottom-full mb-1.5 origin-bottom-right' : 'top-full mt-1.5 origin-top-right' }}">
                                        <button type="button"
                                                wire:click="edit({{ $user->id }})"
                                                @click="open = false"
                                                class="w-full flex items-center gap-2.5 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-blue-50 hover:text-blue-700 transition text-left">
                                            <i class="fa-solid fa-pen-to-square w-4 text-center text-blue-600" aria-hidden="true"></i>
                                            <span>Editar usuario</span>
                                        </button>

                                        <button type="button"
                                                wire:click="sendOnboardingEmailToUser({{ $user->id }}, false)"
                                                wire:confirm="¿Enviar correo de bienvenida con los detalles de cuenta a {{ $user->email }}?"
                                                @click="open = false"
                                                class="w-full flex items-center gap-2.5 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition text-left">
                                            <i class="fa-solid fa-envelope w-4 text-center text-emerald-600" aria-hidden="true"></i>
                                            <span>Reenviar bienvenida</span>
                                        </button>

                                        <button type="button"
                                                wire:click="sendOnboardingEmailToUser({{ $user->id }}, true)"
                                                wire:confirm="¿Generar una NUEVA contraseña temporal para {{ $user->name }} y enviársela por correo?"
                                                @click="open = false"
                                                class="w-full flex items-center gap-2.5 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-amber-50 hover:text-amber-700 transition text-left">
                                            <i class="fa-solid fa-key w-4 text-center text-amber-600" aria-hidden="true"></i>
                                            <span>Nueva clave + Bienvenida</span>
                                        </button>

                                        @if(auth()->id() !== $user->id)
                                            <div class="border-t border-slate-100 my-1"></div>
                                            <button type="button"
                                                    wire:click="delete({{ $user->id }})"
                                                    wire:confirm="¿Estás seguro de eliminar la cuenta de {{ $user->name }}?"
                                                    @click="open = false"
                                                    class="w-full flex items-center gap-2.5 px-3 py-2 text-xs font-bold text-red-600 hover:bg-red-50 transition text-left">
                                                <i class="fa-solid fa-trash-can w-4 text-center text-red-500" aria-hidden="true"></i>
                                                <span>Eliminar cuenta</span>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-slate-400 text-sm">
                                <i class="fa-solid fa-users-slash text-3xl mb-2 block text-slate-300" aria-hidden="true"></i>
                                No se encontraron usuarios con los criterios de búsqueda actuales.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 rounded-b-2xl">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    {{-- MODAL CREAR / EDITAR USUARIO --}}
    @if($isFormModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
             role="dialog"
             aria-modal="true"
             aria-labelledby="user-modal-title"
             @keydown.escape.window="$wire.closeFormModal()">
            <div class="relative bg-white rounded-2xl max-w-2xl w-full shadow-2xl border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 bg-slate-900 text-white flex justify-between items-center border-b border-white/10">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-widest text-brand-lime block">
                            GESTIÓN DE DOTACIÓN
                        </span>
                        <h3 id="user-modal-title" class="text-base font-black flex items-center gap-2">
                            <i class="fa-solid {{ $editingId ? 'fa-user-pen text-indigo-300' : 'fa-user-plus text-brand-lime' }}" aria-hidden="true"></i>
                            <span>{{ $editingId ? 'Editar Usuario' : 'Crear Nuevo Usuario' }}</span>
                        </h3>
                    </div>
                    <button type="button" wire:click="closeFormModal" aria-label="Cerrar ventana" class="text-gray-300 hover:text-white p-1 rounded-md">
                        <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i>
                    </button>
                </div>

                <form wire:submit.prevent="save">
                    <div class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Nombre Completo *</label>
                                <input type="text" wire:model="name" placeholder="Ej: María González Pérez" class="w-full rounded-lg border-slate-300 shadow-sm text-sm">
                                @error('name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Correo Electrónico *</label>
                                <input type="email" wire:model="email" placeholder="nombre@buinseguro.cl" class="w-full rounded-lg border-slate-300 shadow-sm text-sm">
                                @error('email') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Rol en el Sistema *</label>
                                <select wire:model.live="role_id" class="w-full rounded-lg border-slate-300 shadow-sm text-sm font-semibold">
                                    <option value="">-- Seleccione un rol --</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                                @error('role_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">
                                    Anexo Telefónico
                                    <span class="text-[10px] font-normal text-slate-400 normal-case">(Opcional)</span>
                                </label>
                                <input type="text" wire:model="extension" placeholder="Ej: 101" class="w-full rounded-lg border-slate-300 shadow-sm text-sm font-mono">
                                @error('extension') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        {{-- Contraseña + Generador --}}
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-bold uppercase text-slate-600">
                                    Contraseña {{ $editingId ? '(Opcional: dejar vacío para mantener)' : '*' }}
                                </label>
                                <button type="button"
                                        wire:click="generateTemporaryPassword"
                                        class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 inline-flex items-center gap-1">
                                    <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>
                                    <span>Generar clave temporal</span>
                                </button>
                            </div>
                            <div class="flex gap-2">
                                <input type="{{ $showPassword ? 'text' : 'password' }}"
                                       wire:model="password"
                                       placeholder="{{ $editingId ? '••••••••' : 'Mínimo 6 caracteres' }}"
                                       class="flex-1 rounded-lg border-slate-300 shadow-sm text-sm font-mono">
                                <button type="button"
                                        wire:click="$toggle('showPassword')"
                                        aria-label="Mostrar u ocultar contraseña"
                                        class="px-3 rounded-lg border border-slate-300 bg-slate-50 hover:bg-slate-100 text-slate-600 text-xs font-bold transition">
                                    <i class="fa-solid {{ $showPassword ? 'fa-eye-slash' : 'fa-eye' }}" aria-hidden="true"></i>
                                </button>
                            </div>
                            @error('password') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        {{-- Cobertura / Sectores inteligente según Rol --}}
                        @php $selectedRoleName = $this->selectedRoleName; @endphp
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-bold uppercase text-slate-600">Cobertura / Sectores Asignados</label>
                                @if($selectedRoleName === 'Operador' || ! $selectedRoleName)
                                    <div class="flex items-center gap-2 text-[11px] font-bold">
                                        <button type="button" wire:click="selectAllSectors" class="text-blue-600 hover:underline">Seleccionar todos</button>
                                        <span class="text-slate-300">|</span>
                                        <button type="button" wire:click="clearSectors" class="text-slate-500 hover:underline">Limpiar</button>
                                    </div>
                                @endif
                            </div>

                            @if(in_array($selectedRoleName, ['Administrador', 'Supervisor'], true))
                                <div class="p-3 rounded-xl bg-indigo-50 border border-indigo-200 text-xs font-semibold text-indigo-800 flex items-center gap-2">
                                    <i class="fa-solid fa-globe" aria-hidden="true"></i>
                                    <span>El perfil <strong>{{ $selectedRoleName }}</strong> cuenta con visibilidad global sobre todos los sectores automáticamente.</span>
                                </div>
                            @elseif($selectedRoleName === 'Inspector')
                                <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-xs font-semibold text-emerald-800 flex items-center gap-2">
                                    <i class="fa-solid fa-car-side" aria-hidden="true"></i>
                                    <span>El perfil <strong>Inspector</strong> opera directamente sobre los procedimientos y patrullajes asignados a su móvil en turno.</span>
                                </div>
                            @else
                                <div class="bg-slate-50 p-3 border border-slate-200 rounded-xl grid grid-cols-2 sm:grid-cols-3 gap-2">
                                    @foreach($sectors as $sector)
                                        <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                                            <input type="checkbox" wire:model="selectedSectors" value="{{ $sector->id }}" class="rounded border-slate-300 text-brand-cobalt focus:ring-brand-cobalt">
                                            <span class="truncate">{{ $sector->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                            @error('selectedSectors') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        {{-- Opción de envío de correo de Onboarding --}}
                        <label class="flex items-start gap-2.5 p-3.5 rounded-xl bg-blue-50/70 border border-blue-200 cursor-pointer select-none">
                            <input type="checkbox" wire:model="sendOnboardingEmail" class="mt-0.5 rounded border-blue-400 text-brand-cobalt focus:ring-brand-cobalt">
                            <span class="text-xs text-slate-800">
                                <strong class="font-extrabold block">Enviar correo de bienvenida (onboarding) al guardar</strong>
                                <span class="text-[11px] text-slate-600">
                                    Envía un correo institucional a la cuenta con su enlace de acceso, rol asignado, anexo{{ $password ? ' y la contraseña temporal ingresada' : '' }}.
                                </span>
                            </span>
                        </label>
                    </div>

                    <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex justify-end gap-2">
                        <x-cad.button type="button" variant="secondary" wire:click="closeFormModal">Cancelar</x-cad.button>
                        <x-cad.button type="submit" variant="primary">
                            <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                            <span>{{ $editingId ? 'Actualizar Usuario' : 'Crear Usuario' }}</span>
                        </x-cad.button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
