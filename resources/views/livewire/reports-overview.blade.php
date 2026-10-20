<div class="py-6 max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div>
            <h1 class="text-2xl font-extrabold text-ui-fg flex items-center gap-2.5">
                <span class="w-8 h-8 rounded-lg bg-brand-cobalt text-brand-lime inline-flex items-center justify-center text-sm shadow-xs">
                    <i class="fa-solid fa-chart-column" aria-hidden="true"></i>
                </span>
                <span>Reportes Históricos, Estadísticas y Trazabilidad SLA</span>
            </h1>
            <p class="text-xs text-ui-muted mt-1">
                Período consultado: <strong class="font-mono cad-tabular text-ui-fg">{{ $startDate ? \Carbon\Carbon::parse($startDate)->format('d/m/Y') : 'Inicio' }} — {{ $endDate ? \Carbon\Carbon::parse($endDate)->format('d/m/Y') : 'Hoy' }}</strong>
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <x-cad.kpi-card
            label="Total Procedimientos"
            :value="$totalCases"
            tone="cobalt"
            icon="fa-solid fa-folder-open"
            period="Casos en el rango filtrado"
        />
        <x-cad.kpi-card
            label="Casos Cerrados"
            :value="$closedCases"
            tone="completed"
            icon="fa-solid fa-circle-check"
            period="Procedimientos finalizados"
        />
        <x-cad.kpi-card
            label="Tasa de Cierre"
            :value="$totalCases > 0 ? round(($closedCases / $totalCases) * 100) : 0"
            unit="%"
            tone="lime"
            icon="fa-solid fa-percent"
            period="Efectividad operacional"
        />
        <x-cad.kpi-card
            label="SLA Despacho Prom."
            :value="!is_null($avgDispatchSla) ? $avgDispatchSla : 'S/D'"
            :unit="!is_null($avgDispatchSla) ? 'min' : null"
            tone="progress"
            icon="fa-solid fa-stopwatch"
            period="Recepción a despacho"
        />
        @php
            $arrivalTone = is_null($avgArrivalSla) ? 'neutral' : ($avgArrivalSla <= 12 ? 'completed' : ($avgArrivalSla <= 20 ? 'warning' : 'critical'));
        @endphp
        <x-cad.kpi-card
            label="SLA Arribo Prom."
            :value="!is_null($avgArrivalSla) ? $avgArrivalSla : 'S/D'"
            :unit="!is_null($avgArrivalSla) ? 'min' : null"
            :tone="$arrivalTone"
            icon="fa-solid fa-gauge-high"
            period="Despacho a arribo en lugar"
        />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6" wire:ignore>
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <h3 class="font-bold text-gray-700 mb-4 text-center">Procedimientos por Sector</h3>
            <div class="relative h-72 w-full flex justify-center">
                <canvas id="sectorChart"></canvas>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border p-6">
            <h3 class="font-bold text-gray-700 mb-4 text-center">Concentración Territorial (Heatmap)</h3>
            <div id="heatmap-canvas" class="w-full h-72 rounded border border-gray-200"></div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border p-6">
            <h3 class="font-bold text-gray-700 mb-4 text-center">Top Tipos de Incidente</h3>
            <div class="relative h-64 w-full">
                <canvas id="typeChart"></canvas>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border p-6">
            <h3 class="font-bold text-gray-700 mb-4 text-center">Distribución Horaria de Demanda (00:00 - 23:00 hrs)</h3>
            <div class="relative h-64 w-full">
                <canvas id="hourlyChart"></canvas>
            </div>
        </div>
    </div>
    
    <div class="bg-gray-50 rounded-lg shadow-sm border p-6 mb-6">
        <div class="flex justify-between items-center mb-4 border-b pb-2">
            <h3 class="font-bold text-gray-700">Filtros de Búsqueda</h3>
            
            <div class="flex space-x-2">
                <button wire:click="exportToExcel" class="bg-green-600 hover:bg-green-700 text-white text-sm font-bold py-2 px-4 rounded shadow inline-flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Exportar Excel
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Fecha Desde</label>
                <input type="date" wire:model.live="startDate" class="mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Fecha Hasta</label>
                <input type="date" wire:model.live="endDate" class="mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sector</label>
                <x-searchable-select
                    wire:model.live="sector_id"
                    :options="$sectors"
                    placeholder="Todos los sectores"
                    searchPlaceholder="Digite sector..."
                />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Incidente</label>
                <x-searchable-select
                    wire:model.live="procedure_type_id"
                    :options="$procedureTypes"
                    placeholder="Todos los tipos"
                    searchPlaceholder="Digite tipo de incidente..."
                />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Móvil</label>
                <x-searchable-select
                    wire:model.live="device_id"
                    :options="$devices"
                    placeholder="Todos los móviles"
                    searchPlaceholder="Digite móvil..."
                />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Estado</label>
                <select wire:model.live="status_filter" class="mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm">
                    <option value="">Todos los estados</option>
                    <option value="Pendiente">Pendiente</option>
                    <option value="En Camino">En Camino</option>
                    <option value="En el Lugar">En el Lugar</option>
                    <option value="Derivado">Derivado</option>
                    <option value="Cerrado">Cerrado</option>
                </select>
            </div>
        </div>
    </div>

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="min-w-full leading-normal">
            <thead>
                <tr class="bg-gray-800 text-white text-sm leading-normal">
                    <th class="py-3 px-6 text-left">ID / Fecha</th>
                    <th class="py-3 px-6 text-left">Sector / Dirección</th>
                    <th class="py-3 px-6 text-left">Tipo</th>
                    <th class="py-3 px-6 text-center">Estado</th>
                    <th class="py-3 px-6 text-center">SLA / Respuesta</th>
                    <th class="py-3 px-6 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 text-sm font-light">
                @forelse ($procedures as $procedure)
                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                        <td class="py-3 px-6 text-left whitespace-nowrap">
                            <div class="font-bold text-gray-800">#{{ $procedure->id }}</div>
                            <div class="text-xs text-gray-500">{{ $procedure->created_at->format('d/m/Y H:i') }}</div>
                        </td>
                        <td class="py-3 px-6 text-left">
                            <div class="font-bold text-gray-800">{{ $procedure->sector->name }}</div>
                            <div class="text-xs">{{ $procedure->exact_address }}</div>
                        </td>
                        <td class="py-3 px-6 text-left font-medium">
                            {{ $procedure->procedureType->name }}
                            @if($procedure->device)
                                <div class="text-xs text-gray-500">🚓 {{ $procedure->device->name }}</div>
                            @endif
                        </td>
                        <td class="py-3 px-6 text-center">
                            <span class="bg-gray-200 text-gray-700 py-1 px-3 rounded-full text-xs font-bold">
                                {{ $procedure->status }}
                            </span>
                        </td>
                        <td class="py-3 px-6 text-center">
                            @php $sla = $procedure->sla_traffic_light; @endphp
                            <span class="inline-flex items-center gap-1.5 border py-1 px-2.5 rounded-full text-xs font-bold {{ $sla['badge'] }}">
                                <span class="w-2 h-2 rounded-full {{ $sla['dot'] }}"></span>
                                {{ $sla['label'] }}
                            </span>
                        </td>
                        <td class="py-3 px-6 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('procedures.show', $procedure->id) }}" target="_blank" class="text-blue-600 hover:text-blue-800 underline font-bold text-xs">Ver Caso</a>
                                <a href="{{ route('procedures.print', $procedure->id) }}" target="_blank" class="bg-gray-100 hover:bg-gray-200 text-gray-800 border px-2 py-1 rounded text-xs font-bold" title="Imprimir Ficha Oficial / PDF">🖨️ PDF</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-8 px-6 text-center text-gray-500 font-bold">
                            No se encontraron procedimientos para estos filtros.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        
        <div class="p-4 border-t bg-gray-50">
            {{ $procedures->links() }}
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://unpkg.com/deck.gl@latest/dist.min.js"></script>

<script>
    document.addEventListener('livewire:initialized', () => {
        // --- 1. INICIALIZAR GRÁFICO DE DONA POR SECTOR (CHART.JS) ---
        const ctx = document.getElementById('sectorChart').getContext('2d');
        let sectorChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: {!! $chartLabels !!},
                datasets: [{
                    label: 'Cantidad de Procedimientos',
                    data: {!! $chartValues !!},
                    backgroundColor: [
                        '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
            }
        });

        // --- 1B. GRÁFICO TOP TIPOS DE INCIDENTE ---
        const typeCtx = document.getElementById('typeChart').getContext('2d');
        let typeChart = new Chart(typeCtx, {
            type: 'bar',
            data: {
                labels: {!! $typeLabels !!},
                datasets: [{
                    label: 'Casos Registrados',
                    data: {!! $typeValues !!},
                    backgroundColor: '#4F46E5',
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }
            }
        });

        // --- 1C. GRÁFICO DISTRIBUCIÓN HORARIA ---
        const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
        let hourlyChart = new Chart(hourlyCtx, {
            type: 'line',
            data: {
                labels: {!! $hourlyLabels !!},
                datasets: [{
                    label: 'Procedimientos por Hora',
                    data: {!! $hourlyValues !!},
                    borderColor: '#0284C7',
                    backgroundColor: 'rgba(2, 132, 199, 0.15)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }
            }
        });

        // --- 2. INICIALIZAR MAPA DE CALOR (CON DECK.GL) ---
        const map = new google.maps.Map(document.getElementById("heatmap-canvas"), {
            zoom: 13,
            center: { lat: -33.7320, lng: -70.7420 },
            mapTypeId: 'satellite', 
        });

        const {GoogleMapsOverlay} = deck;
        let overlay = new GoogleMapsOverlay({
            layers: []
        });
        overlay.setMap(map);

        @this.updateMap();

        // --- 3. ESCUCHAR ACTUALIZACIONES DE LIVEWIRE ---
        Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
            succeed(({ snapshot, effect }) => {
                let memoData = snapshot?.memo?.data || snapshot?.data || {};

                if (memoData.chartLabels && memoData.chartValues) {
                    sectorChart.data.labels = typeof memoData.chartLabels === 'string' ? JSON.parse(memoData.chartLabels) : memoData.chartLabels;
                    sectorChart.data.datasets[0].data = typeof memoData.chartValues === 'string' ? JSON.parse(memoData.chartValues) : memoData.chartValues;
                    sectorChart.update();
                }

                if (memoData.typeLabels && memoData.typeValues) {
                    typeChart.data.labels = typeof memoData.typeLabels === 'string' ? JSON.parse(memoData.typeLabels) : memoData.typeLabels;
                    typeChart.data.datasets[0].data = typeof memoData.typeValues === 'string' ? JSON.parse(memoData.typeValues) : memoData.typeValues;
                    typeChart.update();
                }

                if (memoData.hourlyLabels && memoData.hourlyValues) {
                    hourlyChart.data.labels = typeof memoData.hourlyLabels === 'string' ? JSON.parse(memoData.hourlyLabels) : memoData.hourlyLabels;
                    hourlyChart.data.datasets[0].data = typeof memoData.hourlyValues === 'string' ? JSON.parse(memoData.hourlyValues) : memoData.hourlyValues;
                    hourlyChart.update();
                }
            })
        });

        Livewire.on('update-heatmap', (event) => {
            let locations = event[0]?.data || event?.data || event;
            
            if (locations && Array.isArray(locations)) {
                const heatmapLayer = new deck.HeatmapLayer({
                    id: 'heatmapLayer',
                    data: locations,
                    getPosition: d => [d.lng, d.lat],
                    getWeight: d => 1,
                    radiusPixels: 35,
                    intensity: 1.5,
                    threshold: 0.05
                });

                overlay.setProps({
                    layers: [heatmapLayer]
                });
            }
        });
    });
</script>