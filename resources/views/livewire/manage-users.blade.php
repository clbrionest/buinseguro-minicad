<div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Administración de Usuarios y Permisos</h2>
    </div>

    <div class="bg-white shadow-md rounded-lg p-6">
        
        @if (session()->has('message'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
                {{ session('message') }}
            </div>
        @endif
        @if (session()->has('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-gray-50 p-6 rounded-lg border mb-8">
            <h3 class="font-bold text-gray-700 mb-4 border-b pb-2">{{ $editingId ? 'Editar Usuario' : 'Crear Nuevo Usuario' }}</h3>
            
            <form wire:submit.prevent="save">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nombre Completo</label>
                        <input type="text" wire:model="name" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                        @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Correo Electrónico</label>
                        <input type="email" wire:model="email" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                        @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Anexo Telefónico (PBX)</label>
                        <input type="text" wire:model="extension" placeholder="Ej: 101" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                        @error('extension') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Contraseña {{ $editingId ? '(Dejar en blanco para no cambiar)' : '' }}</label>
                        <input type="password" wire:model="password" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                        @error('password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="md:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Rol en el Sistema</label>
                        <select wire:model="role_id" class="w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">Seleccione un rol...</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                        @error('role_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sectores Asignados (Visibilidad)</label>
                        <div class="bg-white p-3 border rounded-md shadow-sm grid grid-cols-2 lg:grid-cols-3 gap-2">
                            @foreach($sectors as $sector)
                                <label class="inline-flex items-center">
                                    <input type="checkbox" wire:model="selectedSectors" value="{{ $sector->id }}" class="rounded border-gray-300 text-blue-600 shadow-sm">
                                    <span class="ml-2 text-sm text-gray-700">{{ $sector->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('selectedSectors') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="flex justify-end space-x-3">
                    @if($editingId)
                        <button type="button" wire:click="resetForm" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded">Cancelar</button>
                    @endif
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded shadow">
                        {{ $editingId ? 'Actualizar Usuario' : 'Guardar Usuario' }}
                    </button>
                </div>
            </form>
        </div>

        <table class="min-w-full leading-normal border">
            <thead>
                <tr class="bg-gray-100 text-gray-600 text-sm leading-normal">
                    <th class="py-3 px-6 text-left">Usuario</th>
                    <th class="py-3 px-6 text-center">Anexo PBX</th>
                    <th class="py-3 px-6 text-left">Rol</th>
                    <th class="py-3 px-6 text-left">Sectores Asignados</th>
                    <th class="py-3 px-6 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 text-sm font-light">
                @foreach ($users as $user)
                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                        <td class="py-3 px-6 text-left">
                            <div class="font-bold text-gray-800">{{ $user->name }}</div>
                            <div class="text-xs text-gray-500">{{ $user->email }}</div>
                        </td>
                        <td class="py-3 px-6 text-center">
                            @if($user->extension)
                                <span class="bg-gray-100 px-2 py-1 rounded border text-gray-700 font-bold text-xs">📞 {{ $user->extension }}</span>
                            @else
                                <span class="text-xs text-gray-400 italic">Sin anexo</span>
                            @endif
                        </td>
                        <td class="py-3 px-6 text-left font-bold">
                            <span class="bg-blue-100 text-blue-800 py-1 px-3 rounded-full text-xs">
                                {{ $user->roles->first()->name ?? 'Sin Rol' }}
                            </span>
                        </td>
                        <td class="py-3 px-6 text-left">
                            <div class="flex flex-wrap gap-1">
                                @forelse($user->sectors as $sector)
                                    <span class="bg-gray-200 text-gray-700 py-1 px-2 rounded text-xs">{{ $sector->name }}</span>
                                @empty
                                    <span class="text-xs text-red-500 italic">Ninguno (Ve todo o nada)</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="py-3 px-6 text-center space-x-2">
                            <button wire:click="edit({{ $user->id }})" class="text-blue-500 hover:text-blue-700 font-bold underline">Editar</button>
                            <button onclick="confirm('¿Eliminar usuario?') || event.stopImmediatePropagation()" wire:click="delete({{ $user->id }})" class="text-red-500 hover:text-red-700 font-bold underline">Eliminar</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>