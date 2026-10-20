<div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Mantenedor de Puntos de Interés (Hitos)</h2>
        <p class="text-gray-600 text-sm">Registra los lugares de referencia frecuente en la comuna haciendo clic en el mapa.</p>
    </div>

    <div class="bg-white shadow-md rounded-lg p-6">
        
        @if (session()->has('message'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
                {{ session('message') }}
            </div>
        @endif

        <div class="bg-gray-50 p-6 rounded-lg border mb-8">
            <h3 class="font-bold text-gray-700 mb-4 border-b pb-2">{{ $editingId ? 'Editar Hito' : 'Agregar Nuevo Hito' }}</h3>
            <form wire:submit.prevent="save">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nombre del Lugar</label>
                            <input type="text" wire:model="name" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" placeholder="Ej: Liceo A-131">
                            @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Dirección Base</label>
                            <input type="text" wire:model="address" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" placeholder="Ej: Mac Iver 350, Buin">
                            @error('address') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Latitud</label>
                                <input type="text" wire:model="latitude" readonly class="mt-1 w-full rounded-md border-gray-300 bg-gray-100 shadow-sm text-sm text-gray-500">
                                @error('latitude') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Longitud</label>
                                <input type="text" wire:model="longitude" readonly class="mt-1 w-full rounded-md border-gray-300 bg-gray-100 shadow-sm text-sm text-gray-500">
                                @error('longitude') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <div wire:ignore>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Haz clic en el mapa para fijar la ubicación</label>
                        <div id="map" class="w-full h-64 rounded-md border-2 border-gray-300 shadow-inner"></div>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 mt-4">
                    @if($editingId)
                        <button type="button" wire:click="resetForm" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded">Cancelar</button>
                    @endif
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded shadow">
                        {{ $editingId ? 'Actualizar Hito' : 'Guardar Hito' }}
                    </button>
                </div>
            </form>
        </div>

        <table class="min-w-full leading-normal border">
            <thead>
                <tr class="bg-gray-100 text-gray-600 text-sm leading-normal">
                    <th class="py-3 px-6 text-left">Nombre</th>
                    <th class="py-3 px-6 text-left">Dirección</th>
                    <th class="py-3 px-6 text-left">Coordenadas</th>
                    <th class="py-3 px-6 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 text-sm font-light">
                @foreach ($landmarks as $landmark)
                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                        <td class="py-3 px-6 text-left font-bold text-gray-800">{{ $landmark->name }}</td>
                        <td class="py-3 px-6 text-left">{{ $landmark->address }}</td>
                        <td class="py-3 px-6 text-left text-xs text-gray-500">{{ $landmark->latitude }}, {{ $landmark->longitude }}</td>
                        <td class="py-3 px-6 text-center space-x-2">
                            <button wire:click="edit({{ $landmark->id }})" class="text-blue-500 hover:text-blue-700 font-bold underline">Editar</button>
                            <button onclick="confirm('¿Eliminar hito?') || event.stopImmediatePropagation()" wire:click="delete({{ $landmark->id }})" class="text-red-500 hover:text-red-700 font-bold underline">Eliminar</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        let map;
        let marker;
        // Coordenadas centrales de Buin por defecto
        const buinCenter = { lat: -33.7320, lng: -70.7420 };

        // Inicializamos el mapa
        map = new google.maps.Map(document.getElementById("map"), {
            zoom: 14,
            center: buinCenter,
            mapTypeControl: false,
            streetViewControl: false,
        });

        // Creamos el marcador (inicialmente oculto o en el centro)
        marker = new google.maps.Marker({
            map: map,
            draggable: true,
            animation: google.maps.Animation.DROP,
        });

        // Función para actualizar las variables de Livewire cuando el marcador se mueve
        function updateLivewireCoordinates(latLng) {
            @this.set('latitude', latLng.lat().toFixed(8));
            @this.set('longitude', latLng.lng().toFixed(8));
        }

        // Evento 1: Clic en el mapa
        map.addListener("click", (e) => {
            marker.setPosition(e.latLng);
            updateLivewireCoordinates(e.latLng);
        });

        // Evento 2: Arrastrar el marcador
        marker.addListener("dragend", (e) => {
            updateLivewireCoordinates(e.latLng);
        });

        // Evento 3: Escuchar cuando hacemos clic en "Editar" un hito en la tabla
        // Livewire despachará este evento y el mapa se moverá a las coordenadas de ese hito
        Livewire.hook('commit', ({ succeed }) => {
            succeed(() => {
                let currentLat = parseFloat(@this.get('latitude'));
                let currentLng = parseFloat(@this.get('longitude'));
                
                if (currentLat && currentLng) {
                    let pos = { lat: currentLat, lng: currentLng };
                    marker.setPosition(pos);
                    map.panTo(pos);
                }
            })
        });
    });
</script>