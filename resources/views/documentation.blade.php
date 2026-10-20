<x-app-layout>
    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <!-- Hero Header -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 text-white shadow-lg flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <div class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-blue-400 mb-1">
                    <i class="fa-solid fa-book-open"></i> Documentación Oficial del Sistema • Fase 4
                </div>
                <h1 class="text-2xl font-black tracking-tight">
                    Manual Operativo y Técnico — BuinSeguro CAD v3.0
                </h1>
                <p class="text-xs text-slate-400 mt-1">
                    Guía rápida de referencia para Operadores Telefónicos, Operadores de Cámaras (CCTV), Supervisores de Sala, Inspectores en Terreno y Administradores.
                </p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('dashboard') }}" class="bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow inline-flex items-center gap-1.5 transition">
                    <i class="fa-solid fa-satellite-dish"></i> Ir a Sala de Despacho
                </a>
            </div>
        </div>

        <!-- Quick Architecture & Modules Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <!-- Card 1 -->
            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm border-t-4 border-t-blue-600">
                <div class="flex items-center gap-2.5 mb-3">
                    <span class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                        <i class="fa-solid fa-satellite-dish"></i>
                    </span>
                    <div>
                        <h3 class="font-black text-slate-800 text-sm">1. Despacho CAD y Metadatos</h3>
                        <span class="text-[11px] text-slate-500">Ruta: <code>/dashboard</code></span>
                    </div>
                </div>
                <ul class="text-xs text-slate-600 space-y-2 leading-relaxed">
                    <li><i class="fa-solid fa-check text-blue-600 mr-1"></i> <strong>Doble Categorización:</strong> Registra la tipificación inicial al recibir el llamado y permite recategorizar en terreno sin perder el historial.</li>
                    <li><i class="fa-solid fa-check text-blue-600 mr-1"></i> <strong>Metadatos por Categoría:</strong> Campos dinámicos según el tipo de caso (ej. PPU vehículos, lesionados, especies).</li>
                    <li><i class="fa-solid fa-check text-blue-600 mr-1"></i> <strong>Historial del Llamante:</strong> Al ingresar el teléfono, alerta llamadas o casos previos y posibles <em>pitanzas</em>.</li>
                </ul>
            </div>

            <!-- Card 2 -->
            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm border-t-4 border-t-emerald-600">
                <div class="flex items-center gap-2.5 mb-3">
                    <span class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                        <i class="fa-solid fa-clipboard-check"></i>
                    </span>
                    <div>
                        <h3 class="font-black text-slate-800 text-sm">2. Turnos Rotativos de Sala y Email</h3>
                        <span class="text-[11px] text-slate-500">Ruta: <code>/turno-sala</code></span>
                    </div>
                </div>
                <ul class="text-xs text-slate-600 space-y-2 leading-relaxed">
                    <li><i class="fa-solid fa-check text-emerald-600 mr-1"></i> <strong>3 Turnos Rotativos:</strong> Turno 1 (<code>08:00 a 14:00</code>), Turno 2 (<code>14:00 a 20:00</code>) y Turno 3 (<code>20:00 a 08:00</code>).</li>
                    <li><i class="fa-solid fa-check text-emerald-600 mr-1"></i> <strong>Dotación por Puesto:</strong> 2 <em>Operadores Telefónicos</em> y 4 a 5 <em>Operadores de Cámaras (CCTV)</em> por bloque, con botón de rotación rápida.</li>
                    <li><i class="fa-solid fa-check text-emerald-600 mr-1"></i> <strong>Reporte a Directores:</strong> Al cerrar la guardia se envía un informe estadístico HTML automático por correo a Dirección.</li>
                </ul>
            </div>

            <!-- Card 3 -->
            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm border-t-4 border-t-indigo-600">
                <div class="flex items-center gap-2.5 mb-3">
                    <span class="w-9 h-9 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                        <i class="fa-solid fa-location-crosshairs"></i>
                    </span>
                    <div>
                        <h3 class="font-black text-slate-800 text-sm">3. GPS, Semáforo SLA y Contingencias</h3>
                        <span class="text-[11px] text-slate-500">Rutas: <code>/terreno</code> • <code>/mapa-operativo</code></span>
                    </div>
                </div>
                <ul class="text-xs text-slate-600 space-y-2 leading-relaxed">
                    <li><i class="fa-solid fa-check text-indigo-600 mr-1"></i> <strong>Doble Confirmación de Arribo:</strong> El móvil marca llegada con GPS en terreno (calculando distancia en metros) y la operadora confirma en Central.</li>
                    <li><i class="fa-solid fa-check text-indigo-600 mr-1"></i> <strong>Semáforo SLA:</strong> Verde ($\le 12$ min), Amarillo ($13\text{–}20$ min) y Rojo ($> 20$ min).</li>
                    <li><i class="fa-solid fa-check text-indigo-600 mr-1"></i> <strong>Eventos Masivos (<code>/eventos-masivos</code>):</strong> Colector rápido de reportes para frentes de mal tiempo o emergencias comunales.</li>
                </ul>
            </div>
        </div>

        <!-- Role Matrix & Catalogs -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-7 bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                <h3 class="font-black text-slate-800 text-sm uppercase tracking-wider border-b border-slate-200 pb-3 mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-user-shield text-blue-600"></i>
                    <span>Matriz de Roles y Niveles de Acceso (RBAC)</span>
                </h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs divide-y divide-slate-200">
                        <thead class="bg-slate-50 font-black uppercase text-slate-600">
                            <tr>
                                <th class="py-2.5 px-3 text-left">Rol</th>
                                <th class="py-2.5 px-3 text-left">Alcance Operativo y Permisos</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr>
                                <td class="py-2.5 px-3 font-black text-blue-700 whitespace-nowrap">Administrador</td>
                                <td class="py-2.5 px-3">Acceso total a todos los sectores, escucha de todas las grabaciones PBX, mantenedores/catálogos completos, gestión de usuarios/anexos, hitos y auditoría de seguridad.</td>
                            </tr>
                            <tr>
                                <td class="py-2.5 px-3 font-black text-indigo-700 whitespace-nowrap">Supervisor</td>
                                <td class="py-2.5 px-3">Visibilidad de todos los sectores, escucha de todas las grabaciones PBX, armado de turnos de patrullas y de operadores, estadísticas/heatmap y auditoría.</td>
                            </tr>
                            <tr>
                                <td class="py-2.5 px-3 font-black text-emerald-700 whitespace-nowrap">Operador</td>
                                <td class="py-2.5 px-3">Despacho y monitoreo de sus sectores asignados, historial de llamadas PBX (con reproducción de audio de su propio anexo), turnos de sala y contingencias.</td>
                            </tr>
                            <tr>
                                <td class="py-2.5 px-3 font-black text-amber-700 whitespace-nowrap">Inspector</td>
                                <td class="py-2.5 px-3">Panel móvil en terreno (<code>/terreno</code>), reporte de ubicación y llegada GPS, cambio de estado QAP del móvil, subida de fotografías de evidencia y bitácora.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="lg:col-span-5 bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                <h3 class="font-black text-slate-800 text-sm uppercase tracking-wider border-b border-slate-200 pb-3 mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-sliders text-amber-500"></i>
                    <span>Mantenedores y Catálogos Dinámicos (<code>/mantenedores</code>)</span>
                </h3>
                <p class="text-xs text-slate-600 mb-3">
                    Todas las listas desplegables, categorías y etiquetas del sistema son 100% administrables y validadas desde el módulo de Mantenedores:
                </p>
                <div class="space-y-1.5 text-xs text-slate-700">
                    <div class="bg-slate-50 px-3 py-1.5 rounded border border-slate-200"><strong>1. Sectores / Jurisdicciones:</strong> Cuadrantes comunales de Buin.</div>
                    <div class="bg-slate-50 px-3 py-1.5 rounded border border-slate-200"><strong>2. Tipos de Procedimiento (+ Metadatos):</strong> Tipificaciones y sus campos meta personalizados.</div>
                    <div class="bg-slate-50 px-3 py-1.5 rounded border border-slate-200"><strong>3. Horarios y Puestos de Turno (Sala y Patrullas):</strong> Bloques horarios rotativos y cuotas de operadores telefónicos y de cámaras.</div>
                    <div class="bg-slate-50 px-3 py-1.5 rounded border border-slate-200"><strong>4. Tipificaciones PBX, Bitácora y Estados QAP:</strong> Motivos de llamadas, acciones de bitácora y estados de móviles.</div>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
