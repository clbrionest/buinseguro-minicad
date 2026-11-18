<div id="cad-map-container"
     wire:poll.10s="loadActiveProcedures"
     class="relative w-full h-[calc(100vh-3.5rem)] overflow-hidden bg-slate-950 select-none">

    <!-- Canvas del Mapa -->
    <div id="operational-map" class="w-full h-full" wire:ignore></div>

    <!-- HUD Flotante Superior: Indicadores en Tiempo Real y Herramientas -->
    <div class="absolute top-3 inset-x-3 md:inset-x-5 z-20 pointer-events-none flex flex-wrap items-center justify-between gap-3">
        
        <!-- Indicadores Semánticos de Estado (Izquierda) -->
        <div class="pointer-events-auto flex items-center gap-1.5 sm:gap-2 bg-slate-900/90 dark:bg-ui-card/90 backdrop-blur-md border border-slate-700/60 dark:border-ui-border rounded-xl px-3 py-2 shadow-2xl">
            <!-- Pulsador En Vivo -->
            <div class="flex items-center gap-1.5 pr-2 border-r border-slate-700/80 mr-1" title="Sincronización en tiempo real activa">
                <span class="relative flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                </span>
                <span class="text-[11px] font-black tracking-wider text-slate-200 uppercase hidden sm:inline">EN VIVO</span>
            </div>

            <!-- Chips de Procedimientos -->
            <button type="button"
                    onclick="filterOrPanToStatus('Pendiente')"
                    class="group flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-red-500/15 hover:bg-red-500/25 border border-red-500/40 text-red-400 transition"
                    title="Procedimientos pendientes de asignación o despacho">
                <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                <span class="text-xs font-bold">Pendiente:</span>
                <span class="text-xs font-black font-mono text-white bg-red-600/80 px-1.5 py-0.2 rounded" id="stat-pending">{{ $counts['pending'] ?? 0 }}</span>
            </button>

            <button type="button"
                    onclick="filterOrPanToStatus('En Camino')"
                    class="group flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-500/15 hover:bg-amber-500/25 border border-amber-500/40 text-amber-400 transition"
                    title="Móviles desplazándose hacia el lugar">
                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                <span class="text-xs font-bold hidden sm:inline">En Camino:</span>
                <span class="text-xs font-bold sm:hidden">Ruta:</span>
                <span class="text-xs font-black font-mono text-white bg-amber-600/80 px-1.5 py-0.2 rounded" id="stat-on-way">{{ $counts['on_way'] ?? 0 }}</span>
            </button>

            <button type="button"
                    onclick="filterOrPanToStatus('En el Lugar')"
                    class="group flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-500/15 hover:bg-emerald-500/25 border border-emerald-500/40 text-emerald-400 transition"
                    title="Móviles operando en el sitio del suceso">
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                <span class="text-xs font-bold hidden sm:inline">En Sitio:</span>
                <span class="text-xs font-bold sm:hidden">Sitio:</span>
                <span class="text-xs font-black font-mono text-white bg-emerald-600/80 px-1.5 py-0.2 rounded" id="stat-on-site">{{ $counts['on_site'] ?? 0 }}</span>
            </button>

            <!-- Móviles con GPS -->
            <button type="button"
                    onclick="panToFleet()"
                    class="group flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-blue-500/15 hover:bg-blue-500/25 border border-blue-500/40 text-blue-300 transition"
                    title="Móviles y patrullas con ubicación GPS activa">
                <span class="text-xs">🚓</span>
                <span class="text-xs font-bold hidden sm:inline">Móviles:</span>
                <span class="text-xs font-black font-mono text-white bg-blue-600/80 px-1.5 py-0.2 rounded" id="stat-vehicles">{{ $counts['vehicles'] ?? 0 }}</span>
            </button>
        </div>

        <!-- Barra de Herramientas, Capas y Apariencia del Mapa (Derecha) -->
        <div class="pointer-events-auto flex flex-wrap items-center gap-1.5 bg-slate-900/90 dark:bg-ui-card/90 backdrop-blur-md border border-slate-700/60 dark:border-ui-border rounded-xl p-1.5 shadow-2xl">
            <!-- Selector de Capas de Visualización (Procedimientos, Móviles, Ruta GPS) -->
            <div class="flex items-center bg-slate-800/80 p-0.5 rounded-lg border border-slate-700/50" role="group" aria-label="Capas de visualización en tiempo real">
                <button type="button"
                        id="layer-btn-procedures"
                        onclick="toggleMapLayer('procedures')"
                        class="px-2.5 py-1 text-xs font-bold rounded-md transition flex items-center gap-1 bg-indigo-600 text-white shadow-xs"
                        title="Capa: Posiciones en tiempo real de Procedimientos">
                    <span>📍</span>
                    <span class="hidden lg:inline">Procedimientos</span>
                </button>
                <button type="button"
                        id="layer-btn-vehicles"
                        onclick="toggleMapLayer('vehicles')"
                        class="px-2.5 py-1 text-xs font-bold rounded-md transition flex items-center gap-1 bg-blue-600 text-white shadow-xs"
                        title="Capa: Posición en tiempo real de Móviles">
                    <span>🚓</span>
                    <span class="hidden lg:inline">Móviles</span>
                </button>
                <button type="button"
                        id="layer-btn-routes"
                        onclick="toggleMapLayer('routes')"
                        class="px-2.5 py-1 text-xs font-bold rounded-md transition flex items-center gap-1 bg-emerald-600 text-white shadow-xs"
                        title="Capa: Seguimiento de Ruta GPS de los Móviles">
                    <span>〰️</span>
                    <span class="hidden lg:inline">Ruta GPS</span>
                </button>
            </div>

            <!-- Selector de Skin / Capa Cartográfica -->
            <div class="flex items-center bg-slate-800/80 p-0.5 rounded-lg border border-slate-700/50">
                <button type="button"
                        id="skin-btn-tactical"
                        onclick="setMapSkin('tactical')"
                        class="px-2.5 py-1 text-xs font-bold rounded-md transition flex items-center gap-1 bg-slate-700 text-white shadow-xs"
                        title="Skin CAD Táctico (Oscuro alta visibilidad)">
                    <span>🌙</span>
                    <span class="hidden md:inline">Táctico</span>
                </button>
                <button type="button"
                        id="skin-btn-urban"
                        onclick="setMapSkin('urban')"
                        class="px-2.5 py-1 text-xs font-bold rounded-md transition flex items-center gap-1 text-slate-400 hover:text-white"
                        title="Skin CAD Urbano (Diurno claro)">
                    <span>☀️</span>
                    <span class="hidden md:inline">Urbano</span>
                </button>
                <button type="button"
                        id="skin-btn-satellite"
                        onclick="setMapSkin('satellite')"
                        class="px-2.5 py-1 text-xs font-bold rounded-md transition flex items-center gap-1 text-slate-400 hover:text-white"
                        title="Fotografía Satelital e Híbrida">
                    <span>🛰️</span>
                    <span class="hidden md:inline">Satélite</span>
                </button>
            </div>

            <!-- Botón Centrar Comuna de Buin -->
            <button type="button"
                    onclick="recenterBuin()"
                    class="p-2 text-slate-300 hover:text-white hover:bg-slate-800 rounded-lg transition border border-transparent hover:border-slate-700"
                    title="Centrar mapa en la Comuna de Buin">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M12 2v3m0 14v3M2 12h3m14 0h3"></path>
                </svg>
            </button>

            <!-- Botón Refrescar Datos -->
            <button type="button"
                    onclick="refreshMapData(this)"
                    class="p-2 text-slate-300 hover:text-white hover:bg-slate-800 rounded-lg transition border border-transparent hover:border-slate-700"
                    title="Actualizar datos ahora">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </button>

            <!-- Botón Pantalla Completa -->
            <button type="button"
                    onclick="toggleFullscreen()"
                    class="p-2 text-slate-300 hover:text-white hover:bg-slate-800 rounded-lg transition border border-transparent hover:border-slate-700"
                    title="Modo Pantalla Completa">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Leyenda Flotante Desplegable (Inferior Izquierda) -->
    <div class="absolute bottom-4 left-4 z-20 pointer-events-auto" x-data="{ open: false }">
        <!-- Tarjeta de Leyenda Expandida -->
        <div x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-2 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 translate-y-2 scale-95"
             class="mb-2 bg-slate-900/95 dark:bg-ui-card/95 backdrop-blur-md border border-slate-700/80 rounded-xl p-3 shadow-2xl text-slate-200 w-72 text-xs space-y-2.5">
            
            <div class="flex items-center justify-between border-b border-slate-800 pb-1.5">
                <span class="font-extrabold text-white tracking-wide uppercase text-[11px] flex items-center gap-1.5">
                    <span>🗺️</span> Simbología Operativa CAD
                </span>
                <button type="button" @click="open = false" class="text-slate-400 hover:text-white text-xs">✕</button>
            </div>

            <!-- Tipos de Casos -->
            <div class="space-y-1.5">
                <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block">Procedimientos</span>
                <div class="grid grid-cols-2 gap-1.5 text-[11px]">
                    <div class="flex items-center gap-1.5">
                        <span class="text-sm">🚨</span> Seguridad / Delito
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-sm">🚗</span> Accidente / Tránsito
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-sm">🔥</span> Incendio / Foco
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-sm">🏥</span> Salud / Urgencia
                    </div>
                </div>
            </div>

            <!-- Estados -->
            <div class="space-y-1.5 border-t border-slate-800 pt-2">
                <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block">Estados de Despacho</span>
                <div class="space-y-1 text-[11px]">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-red-600 animate-pulse shrink-0"></span>
                        <span><strong>Pendiente:</strong> Sin móvil asignado</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-amber-500 shrink-0"></span>
                        <span><strong>En Camino:</strong> Patrulla en desplazamiento</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-emerald-500 shrink-0"></span>
                        <span><strong>En el Lugar:</strong> Patrulla operando</span>
                    </div>
                </div>
            </div>

            <!-- Móviles y Rutas GPS -->
            <div class="border-t border-slate-800 pt-2 text-[11px] space-y-1.5">
                <div class="flex items-center gap-2">
                    <span class="text-sm">🚓</span>
                    <span><strong>Móviles GPS:</strong> Posición e identificador en tiempo real.</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm">〰️</span>
                    <span><strong>Ruta GPS:</strong> Estela del recorrido del móvil en tiempo real.</span>
                </div>
            </div>
        </div>

        <!-- Botón Miniatura para Abrir Leyenda -->
        <button type="button"
                @click="open = !open"
                class="flex items-center gap-2 bg-slate-900/90 dark:bg-ui-card/90 backdrop-blur-md border border-slate-700/80 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-200 hover:text-white hover:bg-slate-800 shadow-xl transition">
            <span>🗺️</span>
            <span>Simbología</span>
            <svg class="w-3.5 h-3.5 text-slate-400 transition transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>
    </div>

</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        // Centro de referencia: Plaza de Armas de Buin
        const buinCenter = { lat: -33.7320, lng: -70.7420 };

        // ==========================================
        // 1. ESTILOS CARTOGRÁFICOS PERSONALIZADOS (SKINS)
        // ==========================================
        const CAD_TACTICAL_DARK = [
            { elementType: "geometry", stylers: [{ color: "#0b1120" }] },
            { elementType: "labels.text.stroke", stylers: [{ color: "#0b1120" }, { weight: 2 }] },
            { elementType: "labels.text.fill", stylers: [{ color: "#94a3b8" }] },
            {
                featureType: "administrative.locality",
                elementType: "labels.text.fill",
                stylers: [{ color: "#cbd5e1" }]
            },
            {
                featureType: "administrative.neighborhood",
                elementType: "labels.text.fill",
                stylers: [{ color: "#64748b" }]
            },
            {
                featureType: "poi",
                stylers: [{ visibility: "off" }] // Ocultar comercios irrelevantes para minimizar ruido operativo
            },
            {
                featureType: "road",
                elementType: "geometry",
                stylers: [{ color: "#1e293b" }]
            },
            {
                featureType: "road",
                elementType: "geometry.stroke",
                stylers: [{ color: "#0f172a" }]
            },
            {
                featureType: "road",
                elementType: "labels.text.fill",
                stylers: [{ color: "#64748b" }]
            },
            {
                featureType: "road.highway",
                elementType: "geometry",
                stylers: [{ color: "#29354d" }]
            },
            {
                featureType: "road.highway",
                elementType: "geometry.stroke",
                stylers: [{ color: "#161f30" }]
            },
            {
                featureType: "road.highway",
                elementType: "labels.text.fill",
                stylers: [{ color: "#93c5fd" }]
            },
            {
                featureType: "road.arterial",
                elementType: "geometry",
                stylers: [{ color: "#1e2b42" }]
            },
            {
                featureType: "transit",
                stylers: [{ visibility: "off" }]
            },
            {
                featureType: "water",
                elementType: "geometry",
                stylers: [{ color: "#080e1a" }]
            },
            {
                featureType: "water",
                elementType: "labels.text.fill",
                stylers: [{ color: "#334155" }]
            }
        ];

        const CAD_URBAN_LIGHT = [
            { elementType: "geometry", stylers: [{ color: "#f8fafc" }] },
            { elementType: "labels.text.stroke", stylers: [{ color: "#ffffff" }, { weight: 2 }] },
            { elementType: "labels.text.fill", stylers: [{ color: "#334155" }] },
            {
                featureType: "poi",
                stylers: [{ visibility: "off" }]
            },
            {
                featureType: "road",
                elementType: "geometry",
                stylers: [{ color: "#ffffff" }]
            },
            {
                featureType: "road",
                elementType: "geometry.stroke",
                stylers: [{ color: "#e2e8f0" }]
            },
            {
                featureType: "road",
                elementType: "labels.text.fill",
                stylers: [{ color: "#64748b" }]
            },
            {
                featureType: "road.highway",
                elementType: "geometry",
                stylers: [{ color: "#e2e8f0" }]
            },
            {
                featureType: "road.highway",
                elementType: "geometry.stroke",
                stylers: [{ color: "#cbd5e1" }]
            },
            {
                featureType: "road.arterial",
                elementType: "geometry",
                stylers: [{ color: "#f1f5f9" }]
            },
            {
                featureType: "transit",
                stylers: [{ visibility: "off" }]
            },
            {
                featureType: "water",
                elementType: "geometry",
                stylers: [{ color: "#bfdbfe" }]
            },
            {
                featureType: "water",
                elementType: "labels.text.fill",
                stylers: [{ color: "#2563eb" }]
            }
        ];

        // Determinar skin inicial según preferencia del usuario o tema actual
        const savedSkin = localStorage.getItem('cad-map-skin');
        const currentThemeIsDark = document.documentElement.dataset.theme === 'dark';
        let activeSkin = savedSkin || (currentThemeIsDark ? 'tactical' : 'urban');

        const mapElement = document.getElementById("operational-map");
        if (!mapElement || typeof google === 'undefined' || !google.maps) {
            console.warn('Google Maps no está disponible o el contenedor no fue encontrado.');
            return;
        }

        const map = new google.maps.Map(mapElement, {
            zoom: 14,
            center: buinCenter,
            mapTypeId: activeSkin === 'satellite' ? google.maps.MapTypeId.HYBRID : 'roadmap',
            styles: activeSkin === 'tactical' ? CAD_TACTICAL_DARK : (activeSkin === 'urban' ? CAD_URBAN_LIGHT : []),
            disableDefaultUI: false,
            zoomControl: true,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: false,
        });

        // Aplicar estilos visuales al botón del skin activo
        function updateSkinButtonUI(skin) {
            ['tactical', 'urban', 'satellite'].forEach(s => {
                const btn = document.getElementById(`skin-btn-${s}`);
                if (!btn) return;
                if (s === skin) {
                    btn.className = "px-2.5 py-1 text-xs font-bold rounded-md transition flex items-center gap-1 bg-slate-700 text-white shadow-xs";
                } else {
                    btn.className = "px-2.5 py-1 text-xs font-bold rounded-md transition flex items-center gap-1 text-slate-400 hover:text-white";
                }
            });
        }
        updateSkinButtonUI(activeSkin);

        window.setMapSkin = function(skin) {
            activeSkin = skin;
            localStorage.setItem('cad-map-skin', skin);
            updateSkinButtonUI(skin);

            if (skin === 'satellite') {
                map.setMapTypeId(google.maps.MapTypeId.HYBRID);
                map.setOptions({ styles: [] });
            } else if (skin === 'urban') {
                map.setMapTypeId('roadmap');
                map.setOptions({ styles: CAD_URBAN_LIGHT });
            } else {
                map.setMapTypeId('roadmap');
                map.setOptions({ styles: CAD_TACTICAL_DARK });
            }
        };

        window.recenterBuin = function() {
            map.panTo(buinCenter);
            map.setZoom(14);
        };

        window.refreshMapData = function(btn) {
            if (btn) {
                const svg = btn.querySelector('svg');
                if (svg) svg.classList.add('animate-spin');
                setTimeout(() => { if (svg) svg.classList.remove('animate-spin'); }, 800);
            }
            Livewire.dispatch('loadActiveProcedures');
        };

        window.toggleFullscreen = function() {
            const container = document.getElementById('cad-map-container');
            if (!document.fullscreenElement) {
                if (container.requestFullscreen) {
                    container.requestFullscreen();
                } else if (container.webkitRequestFullscreen) {
                    container.webkitRequestFullscreen();
                }
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        };

        // ==========================================
        // 2. GENERACIÓN DINÁMICA DE MARCADORES VECTORIALES (SVG)
        // ==========================================
        function getStatusColor(status) {
            switch (status) {
                case 'Pendiente': return '#DC2626'; // Rojo de alta prioridad
                case 'En Camino': return '#D97706'; // Ámbar / Dorado
                case 'En el Lugar': return '#059669'; // Verde Esmeralda
                case 'Derivado': return '#6366F1'; // Índigo
                default: return '#2563EB'; // Azul
            }
        }

        function createProcedureIcon(type, status) {
            const color = getStatusColor(status);
            const lower = (type || '').toLowerCase();
            let iconSvg = '';

            if (lower.includes('accidente') || lower.includes('tránsito') || lower.includes('transito') || lower.includes('vehicular') || lower.includes('choque')) {
                // Auto / Vehicular
                iconSvg = `<path fill="#FFFFFF" d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z" transform="scale(0.85) translate(4, 2)"/>`;
            } else if (lower.includes('robo') || lower.includes('delito') || lower.includes('seguridad') || lower.includes('portonazo') || lower.includes('sospechoso')) {
                // Escudo / Seguridad
                iconSvg = `<path fill="#FFFFFF" d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z" transform="scale(0.85) translate(4, 2)"/>`;
            } else if (lower.includes('incendio') || lower.includes('fuego') || lower.includes('quema') || lower.includes('gas') || lower.includes('fuga')) {
                // Fuego / Emergencia
                iconSvg = `<path fill="#FFFFFF" d="M12 23c-4.97 0-9-4.03-9-9 0-4.07 3.03-7.62 6.64-8.77l.86-.27v2.32c0 1.25.75 2.37 1.9 2.85l1.6.66-.4 1.69c-.43 1.83.6 3.65 2.36 4.19l.79.24-.46.73C15.34 22.02 13.72 23 12 23zM13.5 2c-.17 0-.34.02-.5.06-2.1.53-3.66 2.36-3.94 4.54l1.97.25c.16-1.24 1.05-2.28 2.25-2.61.16-.04.31-.06.47-.06.7 0 1.34.33 1.74.88l1.62-1.18C16.3 2.76 14.94 2 13.5 2z" transform="scale(0.85) translate(4, 2)"/>`;
            } else if (lower.includes('salud') || lower.includes('samu') || lower.includes('medico') || lower.includes('lesionado') || lower.includes('herido')) {
                // Cruz Médica
                iconSvg = `<path fill="#FFFFFF" d="M19 10.5h-5.5V5c0-.55-.45-1-1-1h-1c-.55 0-1 .45-1 1v5.5H5c-.55 0-1 .45-1 1v1c0 .55.45 1 1 1h5.5V19c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-5.5H19c.55 0 1-.45 1-1v-1c0-.55-.45-1-1-1z" transform="scale(0.85) translate(4, 2)"/>`;
            } else {
                // Alerta / Genérico
                iconSvg = `<path fill="#FFFFFF" d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z" transform="scale(0.85) translate(4, 2)"/>`;
            }

            const svg = `
            <svg xmlns="http://www.w3.org/2000/svg" width="38" height="48" viewBox="0 0 38 48">
                <defs>
                    <filter id="shadow" x="-20%" y="-20%" width="140%" height="140%">
                        <feDropShadow dx="0" dy="2" stdDeviation="2.5" flood-color="#000000" flood-opacity="0.6"/>
                    </filter>
                </defs>
                <path d="M19 1 C9.06 1 1 9.06 1 19 C1 28.5 19 46 19 46 C19 46 37 28.5 37 19 C37 9.06 28.94 1 19 1 Z" 
                      fill="${color}" stroke="#FFFFFF" stroke-width="2" filter="url(#shadow)"/>
                <circle cx="19" cy="18" r="11" fill="rgba(0, 0, 0, 0.25)"/>
                <g transform="translate(5, 4)">
                    ${iconSvg}
                </g>
            </svg>`;

            return {
                url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg),
                scaledSize: new google.maps.Size(38, 48),
                anchor: new google.maps.Point(19, 46)
            };
        }

        function getVehicleStatusColor(status) {
            switch (status) {
                case 'Disponible': return '#10B981'; // Verde
                case 'Despachado':
                case 'En Procedimiento': return '#3B82F6'; // Azul táctico
                case 'En Traslado': return '#F59E0B'; // Ámbar
                default: return '#64748B'; // Gris neutro
            }
        }

        function createVehicleIcon(name, status) {
            const statusColor = getVehicleStatusColor(status);
            const safeName = (name || 'PATRULLA').toUpperCase().substring(0, 8);

            const svg = `
            <svg xmlns="http://www.w3.org/2000/svg" width="82" height="34" viewBox="0 0 82 34">
                <defs>
                    <filter id="vshadow" x="-10%" y="-10%" width="120%" height="130%">
                        <feDropShadow dx="0" dy="2" stdDeviation="2" flood-color="#000000" flood-opacity="0.65"/>
                    </filter>
                </defs>
                <rect x="2" y="2" width="78" height="26" rx="13" fill="#0F172A" stroke="${statusColor}" stroke-width="2" filter="url(#vshadow)"/>
                <circle cx="15" cy="15" r="7.5" fill="${statusColor}"/>
                <path fill="#FFFFFF" d="M15 11c-.5 0-.9.3-1 .8l-.7 2.1c-.5.2-.8.6-.8 1.1v2.5c0 .3.2.5.5.5h.5c.3 0 .5-.2.5-.5V17h3v.5c0 .3.2.5.5.5h.5c.3 0 .5-.2.5-.5v-2.5c0-.5-.3-.9-.8-1.1l-.7-2.1c-.1-.5-.5-.8-1-.8zm-1.8 3.5l.5-1.5h2.6l.5 1.5h-3.6z" transform="scale(0.8) translate(3.8, 3.8)"/>
                <text x="47" y="19" fill="#F8FAFC" font-family="Inter, system-ui, sans-serif" font-size="11" font-weight="800" text-anchor="middle" letter-spacing="0.5">${safeName}</text>
                <polygon points="36,28 46,28 41,33" fill="${statusColor}"/>
            </svg>`;

            return {
                url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg),
                scaledSize: new google.maps.Size(82, 34),
                anchor: new google.maps.Point(41, 33)
            };
        }

        // ==========================================
        // 3. RENDERIZADO DE MARCADORES, RUTAS GPS Y CAPAS
        // ==========================================
        let markers = {};
        let vehicleMarkers = {};
        let vehicleRoutePolylines = {};
        let procedureDispatchLines = {};
        let currentLocations = [];
        let currentVehicles = [];
        const infoWindow = new google.maps.InfoWindow();

        let layerVisibility = {
            procedures: true,
            vehicles: true,
            routes: true,
        };

        window.toggleMapLayer = function(layerName) {
            if (!(layerName in layerVisibility)) return;
            layerVisibility[layerName] = !layerVisibility[layerName];
            const isVisible = layerVisibility[layerName];

            const btn = document.getElementById('layer-btn-' + layerName);
            if (btn) {
                const activeClasses = {
                    procedures: 'bg-indigo-600 text-white shadow-xs',
                    vehicles: 'bg-blue-600 text-white shadow-xs',
                    routes: 'bg-emerald-600 text-white shadow-xs',
                }[layerName];

                if (isVisible) {
                    btn.className = `px-2.5 py-1 text-xs font-bold rounded-md transition flex items-center gap-1 ${activeClasses}`;
                } else {
                    btn.className = 'px-2.5 py-1 text-xs font-bold rounded-md transition flex items-center gap-1 text-slate-400 hover:text-white opacity-60';
                }
            }

            if (layerName === 'procedures') {
                for (let id in markers) {
                    markers[id].setMap(isVisible ? map : null);
                }
                for (let id in procedureDispatchLines) {
                    procedureDispatchLines[id].setMap(isVisible ? map : null);
                }
            } else if (layerName === 'vehicles') {
                for (let id in vehicleMarkers) {
                    vehicleMarkers[id].setMap(isVisible ? map : null);
                }
            } else if (layerName === 'routes') {
                for (let id in vehicleRoutePolylines) {
                    vehicleRoutePolylines[id].setMap(isVisible ? map : null);
                }
            }
        };

        window.focusVehicleRoute = function(vehicleId) {
            const v = currentVehicles.find(item => parseInt(item.id) === parseInt(vehicleId));
            if (!v) return;
            if (Array.isArray(v.route) && v.route.length > 1) {
                const bounds = new google.maps.LatLngBounds();
                v.route.forEach(pt => bounds.extend({ lat: parseFloat(pt.lat), lng: parseFloat(pt.lng) }));
                map.fitBounds(bounds);
            } else {
                map.panTo({ lat: v.lat, lng: v.lng });
                map.setZoom(16);
            }
        };

        function renderProcedureVectors(locations, vehicles) {
            const activeProcIds = locations.map(l => l.id);
            for (let id in procedureDispatchLines) {
                if (!activeProcIds.includes(parseInt(id))) {
                    procedureDispatchLines[id].setMap(null);
                    delete procedureDispatchLines[id];
                }
            }

            const vehicleMap = {};
            (vehicles || []).forEach(v => { vehicleMap[v.id] = v; });

            locations.forEach(loc => {
                if (loc.device_id && vehicleMap[loc.device_id] && (loc.status === 'En Camino' || loc.status === 'En el Lugar')) {
                    const v = vehicleMap[loc.device_id];
                    const path = [
                        { lat: v.lat, lng: v.lng },
                        { lat: loc.lat, lng: loc.lng }
                    ];
                    const strokeColor = loc.status === 'En Camino' ? '#F59E0B' : '#10B981';

                    if (procedureDispatchLines[loc.id]) {
                        procedureDispatchLines[loc.id].setPath(path);
                        procedureDispatchLines[loc.id].setMap(layerVisibility.procedures ? map : null);
                    } else {
                        procedureDispatchLines[loc.id] = new google.maps.Polyline({
                            path: path,
                            geodesic: true,
                            strokeOpacity: 0,
                            icons: [{
                                icon: {
                                    path: 'M 0,-1 0,1',
                                    strokeOpacity: 0.85,
                                    strokeColor: strokeColor,
                                    scale: 3
                                },
                                offset: '0',
                                repeat: '14px'
                            }],
                            map: layerVisibility.procedures ? map : null,
                        });
                    }
                } else if (procedureDispatchLines[loc.id]) {
                    procedureDispatchLines[loc.id].setMap(null);
                    delete procedureDispatchLines[loc.id];
                }
            });
        }

        function renderMarkers(locations) {
            currentLocations = locations;
            let activeIds = locations.map(loc => loc.id);

            // Eliminar marcadores cerrados
            for (let id in markers) {
                if (!activeIds.includes(parseInt(id))) {
                    markers[id].setMap(null);
                    delete markers[id];
                }
            }

            // Crear o actualizar
            locations.forEach(loc => {
                let position = { lat: loc.lat, lng: loc.lng };
                let icon = createProcedureIcon(loc.type, loc.status);
                let animation = loc.status === 'Pendiente' ? google.maps.Animation.BOUNCE : null;

                if (markers[loc.id]) {
                    markers[loc.id].setPosition(position);
                    markers[loc.id].setIcon(icon);
                    markers[loc.id].setAnimation(animation);
                    markers[loc.id].setMap(layerVisibility.procedures ? map : null);
                } else {
                    let marker = new google.maps.Marker({
                        position: position,
                        map: layerVisibility.procedures ? map : null,
                        icon: icon,
                        animation: animation,
                        title: `#${loc.id} - ${loc.type} (${loc.status})`
                    });

                    marker.addListener('click', () => {
                        let statusColorClass = 'bg-blue-100 text-blue-800 border-blue-200';
                        if (loc.status === 'Pendiente') statusColorClass = 'bg-red-100 text-red-800 border-red-200';
                        else if (loc.status === 'En Camino') statusColorClass = 'bg-amber-100 text-amber-800 border-amber-200';
                        else if (loc.status === 'En el Lugar') statusColorClass = 'bg-emerald-100 text-emerald-800 border-emerald-200';

                        let content = `
                            <div class="cad-info-window font-sans text-left p-2.5 min-w-[260px] max-w-[320px] text-slate-900 bg-white rounded-xl shadow-lg">
                                <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-2 mb-2">
                                    <span class="text-xs font-mono font-black text-slate-600 bg-slate-100 px-2 py-0.5 rounded">#${loc.id}</span>
                                    <span class="text-[11px] font-extrabold px-2 py-0.5 rounded-full border ${statusColorClass}">${loc.status}</span>
                                </div>
                                <h4 class="font-black text-sm text-slate-900 leading-snug mb-1">${loc.type}</h4>
                                <p class="text-xs text-slate-600 mb-1.5 flex items-start gap-1">
                                    <span class="text-slate-400 shrink-0">📍</span>
                                    <span>${loc.address}</span>
                                </p>
                                <div class="text-[11px] text-slate-500 mb-2 flex items-center justify-between bg-slate-50 p-1.5 rounded border border-slate-100">
                                    <span>Sector: <strong>${loc.sector}</strong></span>
                                    ${loc.sla_label ? `<span class="font-bold text-slate-700">⏱ ${loc.sla_label}</span>` : ''}
                                </div>
                                <div class="flex items-center justify-between text-xs border-t border-slate-100 pt-2">
                                    <span class="text-slate-600 text-[11px]">Móvil: <strong class="text-slate-900 font-bold">${loc.device}</strong></span>
                                    <a href="/procedimientos/${loc.id}" target="_blank" class="inline-flex items-center gap-1 font-bold text-xs text-blue-600 hover:text-blue-800 hover:underline">
                                        Ficha Completa &rarr;
                                    </a>
                                </div>
                            </div>
                        `;
                        infoWindow.setContent(content);
                        infoWindow.open(map, marker);
                    });

                    markers[loc.id] = marker;
                }
            });

            renderProcedureVectors(locations, currentVehicles);
        }

        function renderVehicles(vehicles) {
            currentVehicles = vehicles;
            if (!Array.isArray(vehicles)) return;
            let activeIds = vehicles.map(v => v.id);

            for (let id in vehicleMarkers) {
                if (!activeIds.includes(parseInt(id))) {
                    vehicleMarkers[id].setMap(null);
                    delete vehicleMarkers[id];
                }
            }

            for (let id in vehicleRoutePolylines) {
                if (!activeIds.includes(parseInt(id))) {
                    vehicleRoutePolylines[id].setMap(null);
                    delete vehicleRoutePolylines[id];
                }
            }

            vehicles.forEach(v => {
                let position = { lat: v.lat, lng: v.lng };
                let icon = createVehicleIcon(v.name, v.status);
                let routeColor = v.status === 'En Procedimiento' ? '#38BDF8' : '#10B981';

                // 1. Dibujar o actualizar la ruta GPS recorrida por el móvil
                if (Array.isArray(v.route) && v.route.length > 1) {
                    const pathCoords = v.route.map(pt => ({ lat: parseFloat(pt.lat), lng: parseFloat(pt.lng) }));

                    if (vehicleRoutePolylines[v.id]) {
                        vehicleRoutePolylines[v.id].setPath(pathCoords);
                        vehicleRoutePolylines[v.id].setOptions({ strokeColor: routeColor });
                        vehicleRoutePolylines[v.id].setMap(layerVisibility.routes ? map : null);
                    } else {
                        vehicleRoutePolylines[v.id] = new google.maps.Polyline({
                            path: pathCoords,
                            geodesic: true,
                            strokeColor: routeColor,
                            strokeOpacity: 0.85,
                            strokeWeight: 4,
                            icons: [{
                                icon: {
                                    path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
                                    scale: 2.5,
                                    strokeColor: '#FFFFFF',
                                    fillColor: routeColor,
                                    fillOpacity: 1
                                },
                                offset: '100%',
                                repeat: '90px'
                            }],
                            map: layerVisibility.routes ? map : null,
                        });
                    }
                } else if (vehicleRoutePolylines[v.id]) {
                    vehicleRoutePolylines[v.id].setMap(null);
                    delete vehicleRoutePolylines[v.id];
                }

                // 2. Dibujar o actualizar el marcador de posición en vivo del móvil
                if (vehicleMarkers[v.id]) {
                    vehicleMarkers[v.id].setPosition(position);
                    vehicleMarkers[v.id].setIcon(icon);
                    vehicleMarkers[v.id].setMap(layerVisibility.vehicles ? map : null);
                } else {
                    let marker = new google.maps.Marker({
                        position: position,
                        map: layerVisibility.vehicles ? map : null,
                        icon: icon,
                        title: `🚓 ${v.name} (${v.status})`
                    });

                    marker.addListener('click', () => {
                        const latestV = currentVehicles.find(item => item.id === v.id) || v;
                        const routePtsCount = Array.isArray(latestV.route) ? latestV.route.length : 0;
                        const patrolHtml = latestV.active_patrol
                            ? `<div class="mt-1.5 p-1.5 rounded bg-indigo-50 border border-indigo-200 text-[11px] flex items-center justify-between">
                                   <span class="font-bold text-indigo-900">🛡️ ${latestV.active_patrol.folio} (${latestV.active_patrol.validated}/${latestV.active_patrol.total} hitos)</span>
                                   <a href="/patrullajes-preventivos/${latestV.active_patrol.id}" target="_blank" class="font-bold text-indigo-600 underline">Ver</a>
                               </div>`
                            : '';

                        let content = `
                            <div class="cad-info-window font-sans text-left p-2.5 min-w-[250px] max-w-[310px] text-slate-900 bg-white rounded-xl shadow-lg">
                                <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-2 mb-2">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-base">🚓</span>
                                        <span class="font-black text-sm text-slate-900">${latestV.name}</span>
                                    </div>
                                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">${latestV.status}</span>
                                </div>
                                <div class="space-y-1 text-xs text-slate-600 mb-2">
                                    <p><strong class="text-slate-700">Sector Asignado:</strong> ${latestV.sector}</p>
                                    <p><strong class="text-slate-700">Tripulación:</strong> ${latestV.crew}</p>
                                    <p><strong class="text-slate-700">Procedimientos Activos:</strong> ${latestV.active_procedures_count || 0}</p>
                                    <p><strong class="text-slate-700">Puntos GPS en Ruta:</strong> <span class="font-mono font-bold">${routePtsCount}</span></p>
                                    ${patrolHtml}
                                </div>
                                <div class="text-[10px] text-slate-400 border-t border-slate-100 pt-1.5 flex items-center justify-between gap-2">
                                    <span>Último GPS: <strong class="font-mono text-slate-600">${latestV.updated}</strong></span>
                                    <button type="button" onclick="focusVehicleRoute(${latestV.id})" class="text-[11px] font-bold text-blue-600 hover:underline">
                                        Enfocar Ruta &rarr;
                                    </button>
                                </div>
                            </div>
                        `;
                        infoWindow.setContent(content);
                        infoWindow.open(map, marker);
                    });

                    vehicleMarkers[v.id] = marker;
                }
            });

            renderProcedureVectors(currentLocations, vehicles);
        }

        // ==========================================
        // 4. ACCIONES INTERACTIVAS DEL HUD
        // ==========================================
        window.filterOrPanToStatus = function(status) {
            const matches = currentLocations.filter(loc => loc.status === status);
            if (matches.length > 0) {
                if (matches.length === 1) {
                    map.panTo({ lat: matches[0].lat, lng: matches[0].lng });
                    map.setZoom(16);
                    if (markers[matches[0].id]) {
                        google.maps.event.trigger(markers[matches[0].id], 'click');
                    }
                } else {
                    const bounds = new google.maps.LatLngBounds();
                    matches.forEach(m => bounds.extend({ lat: m.lat, lng: m.lng }));
                    map.fitBounds(bounds);
                }
            } else {
                recenterBuin();
            }
        };

        window.panToFleet = function() {
            if (currentVehicles.length > 0) {
                const bounds = new google.maps.LatLngBounds();
                currentVehicles.forEach(v => bounds.extend({ lat: v.lat, lng: v.lng }));
                map.fitBounds(bounds);
            } else {
                recenterBuin();
            }
        };

        // ==========================================
        // 5. CARGA INICIAL Y ACTUALIZACIÓN EN VIVO
        // ==========================================
        let initialLocations = @json($locations);
        let initialVehicles = @json($vehicles);
        renderMarkers(initialLocations);
        renderVehicles(initialVehicles);

        Livewire.on('map-updated', (event) => {
            let payload = event[0] || event;
            let newLocations = payload?.data || [];
            let newVehicles = payload?.vehicles || [];
            let newCounts = payload?.counts || null;

            if (newCounts) {
                const pEl = document.getElementById('stat-pending');
                const wEl = document.getElementById('stat-on-way');
                const sEl = document.getElementById('stat-on-site');
                const vEl = document.getElementById('stat-vehicles');
                if (pEl) pEl.textContent = newCounts.pending ?? 0;
                if (wEl) wEl.textContent = newCounts.on_way ?? 0;
                if (sEl) sEl.textContent = newCounts.on_site ?? 0;
                if (vEl) vEl.textContent = newCounts.vehicles ?? 0;
            }

            if (Array.isArray(newLocations)) {
                renderMarkers(newLocations);
            }
            if (Array.isArray(newVehicles)) {
                renderVehicles(newVehicles);
            }
        });
    });
</script>
