<div class="py-6 max-w-[1600px] mx-auto sm:px-6 lg:px-8">
    
    <div class="mb-4 flex flex-col md:flex-row justify-between items-center bg-gray-900 p-4 rounded-lg shadow">
        <h2 class="text-2xl font-black text-white tracking-wider">📡 MAPA OPERATIVO EN VIVO</h2>
        
        <div wire:poll.10s="loadActiveProcedures" class="flex flex-wrap gap-4 mt-4 md:mt-0">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-red-600 animate-pulse"></span>
                <span class="text-sm text-gray-300 font-bold">Pendiente (Sin Despacho)</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-yellow-400"></span>
                <span class="text-sm text-gray-300 font-bold">En Camino</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                <span class="text-sm text-gray-300 font-bold">En el Lugar</span>
            </div>
            <div class="flex items-center gap-2 border-l border-gray-700 pl-4">
                <span class="w-3 h-3 rounded-full bg-emerald-400"></span>
                <span class="text-sm text-emerald-300 font-bold">🚓 Móviles con GPS</span>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-xl border-4 border-gray-800 p-1" wire:ignore>
        <div id="operational-map" class="w-full h-[75vh] rounded"></div>
    </div>
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        const buinCenter = { lat: -33.7320, lng: -70.7420 };
        
        // Creamos el mapa con un estilo oscuro/grisáceo para que resalten los pines
        const map = new google.maps.Map(document.getElementById("operational-map"), {
            zoom: 14,
            center: buinCenter,
            mapTypeId: 'roadmap',
            disableDefaultUI: false,
        });

        let markers = {};
        let vehicleMarkers = {};
        const infoWindow = new google.maps.InfoWindow();

        // Iconos de colores estándar de Google Maps
        const icons = {
            'Pendiente': 'http://maps.google.com/mapfiles/ms/icons/red-dot.png',
            'En Camino': 'http://maps.google.com/mapfiles/ms/icons/yellow-dot.png',
            'En el Lugar': 'http://maps.google.com/mapfiles/ms/icons/blue-dot.png',
            'Vehicle': 'http://maps.google.com/mapfiles/ms/icons/green-dot.png'
        };

        // Función para renderizar los pines de procedimientos
        function renderMarkers(locations) {
            let activeIds = locations.map(loc => loc.id);

            // 1. Eliminar del mapa los casos que ya se cerraron
            for (let id in markers) {
                if (!activeIds.includes(parseInt(id))) {
                    markers[id].setMap(null);
                    delete markers[id];
                }
            }

            // 2. Crear o actualizar los pines
            locations.forEach(loc => {
                let position = { lat: loc.lat, lng: loc.lng };
                let iconUrl = icons[loc.status] || icons['Pendiente'];
                // ¡Magia! Si está Pendiente, el pin salta en el mapa llamando la atención
                let animation = loc.status === 'Pendiente' ? google.maps.Animation.BOUNCE : null;

                if (markers[loc.id]) {
                    markers[loc.id].setPosition(position);
                    markers[loc.id].setIcon(iconUrl);
                    markers[loc.id].setAnimation(animation);
                } else {
                    // Crear un pin nuevo
                    let marker = new google.maps.Marker({
                        position: position,
                        map: map,
                        icon: iconUrl,
                        animation: animation,
                        title: loc.type
                    });

                    // Tarjeta de información al hacer clic en el pin
                    marker.addListener('click', () => {
                        let content = `
                            <div class="p-2 min-w-[220px]">
                                <h3 class="font-bold text-gray-900 text-base mb-1">#${loc.id} - ${loc.type}</h3>
                                <p class="text-xs text-gray-600 mb-2 font-medium">📍 ${loc.address}</p>
                                <div class="flex justify-between items-center border-t pt-2">
                                    <span class="text-xs px-2 py-1 rounded font-bold ${loc.status === 'Pendiente' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'}">${loc.status}</span>
                                    <span class="bg-gray-800 text-white text-xs px-2 py-1 rounded font-bold">🚓 ${loc.device}</span>
                                </div>
                                <div class="mt-3 text-center">
                                    <a href="/procedimientos/${loc.id}" target="_blank" class="text-blue-600 font-bold text-xs hover:underline">Ver Ficha Completa →</a>
                                </div>
                            </div>
                        `;
                        infoWindow.setContent(content);
                        infoWindow.open(map, marker);
                    });

                    markers[loc.id] = marker;
                }
            });
        }

        // Función para renderizar los móviles con GPS reportado
        function renderVehicles(vehicles) {
            if (!Array.isArray(vehicles)) return;
            let activeIds = vehicles.map(v => v.id);

            for (let id in vehicleMarkers) {
                if (!activeIds.includes(parseInt(id))) {
                    vehicleMarkers[id].setMap(null);
                    delete vehicleMarkers[id];
                }
            }

            vehicles.forEach(v => {
                let position = { lat: v.lat, lng: v.lng };

                if (vehicleMarkers[v.id]) {
                    vehicleMarkers[v.id].setPosition(position);
                } else {
                    let marker = new google.maps.Marker({
                        position: position,
                        map: map,
                        icon: icons['Vehicle'],
                        title: `🚓 ${v.name} (${v.status})`
                    });

                    marker.addListener('click', () => {
                        let content = `
                            <div class="p-2 min-w-[210px]">
                                <h3 class="font-black text-gray-900 text-sm mb-1">🚓 ${v.name}</h3>
                                <p class="text-xs text-emerald-700 font-bold mb-1">Estado: ${v.status}</p>
                                <p class="text-xs text-gray-600">Sector: <strong>${v.sector}</strong></p>
                                <p class="text-xs text-gray-600">Tripulación: ${v.crew}</p>
                                <p class="text-[11px] text-gray-400 mt-1 border-t pt-1">Último reporte GPS: ${v.updated}</p>
                            </div>
                        `;
                        infoWindow.setContent(content);
                        infoWindow.open(map, marker);
                    });

                    vehicleMarkers[v.id] = marker;
                }
            });
        }

        // Cargar los pines la primera vez que se abre la pantalla
        let initialLocations = @json($locations);
        let initialVehicles = @json($vehicles);
        renderMarkers(initialLocations);
        renderVehicles(initialVehicles);

        // Escuchar cada 10 segundos el evento silencioso de Livewire para actualizar
        Livewire.on('map-updated', (event) => {
            let payload = event[0] || event;
            let newLocations = payload?.data || payload;
            let newVehicles = payload?.vehicles || [];
            
            if (newLocations && Array.isArray(newLocations)) {
                renderMarkers(newLocations);
            }
            if (newVehicles && Array.isArray(newVehicles)) {
                renderVehicles(newVehicles);
            }
        });
    });
</script>