<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>BuinSeguro CAD — Central de Comunicaciones y Despacho</title>

        <!-- Fonts & Icons -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-100 antialiased bg-slate-950 selection:bg-blue-600 selection:text-white">
        <div class="min-h-screen flex flex-col lg:flex-row">
            <!-- Left Panel: Institutional Command Center Branding -->
            <div class="hidden lg:flex lg:w-7/12 relative bg-gradient-to-br from-slate-950 via-slate-900 to-blue-950 p-12 flex-col justify-between border-r border-slate-800 overflow-hidden">
                <!-- Subtle tactical grid background -->
                <div class="absolute inset-0 opacity-10 pointer-events-none" style="background-image: radial-gradient(#3b82f6 1px, transparent 1px); background-size: 28px 28px;"></div>

                <!-- Header Brand -->
                <div class="relative z-10 flex items-center justify-between">
                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-xl bg-blue-600 flex items-center justify-center shadow-lg shadow-blue-600/30 border border-blue-400/30">
                            <i class="fa-solid fa-shield-halved text-white text-2xl"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-black text-xl tracking-wider text-white">BUINSEGURO</span>
                                <span class="bg-blue-500/20 border border-blue-400/30 text-blue-300 text-[10px] font-black uppercase px-2 py-0.5 rounded">CAD v3.0</span>
                            </div>
                            <p class="text-xs text-slate-400 font-semibold uppercase tracking-widest">Dirección de Seguridad Pública • I. Municipalidad de Buin</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 bg-slate-900/90 border border-slate-700/80 px-3 py-1.5 rounded-full text-xs font-bold text-emerald-400">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>SISTEMA OPERATIVO</span>
                    </div>
                </div>

                <!-- Main Hero Info -->
                <div class="relative z-10 my-auto max-w-xl">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-950/80 border border-blue-800/80 text-blue-300 text-xs font-bold uppercase tracking-wider mb-4">
                        <i class="fa-solid fa-satellite-dish"></i> Central de Comunicaciones y Despacho
                    </div>
                    <h1 class="text-4xl font-black text-white tracking-tight leading-tight">
                        Plataforma Integrada de Monitoreo, Despacho y Gestión Operativa
                    </h1>
                    <p class="mt-4 text-slate-300 text-sm leading-relaxed">
                        Sistema exclusivo para operadores de central, supervisores de sala, inspectores en terreno y dirección ejecutiva. Integrado con telefonía IP Asterisk PBX, georreferenciación de flota en vivo y control de niveles de servicio (SLA).
                    </p>

                    <!-- Feature Cards -->
                    <div class="grid grid-cols-3 gap-3 mt-8">
                        <div class="bg-slate-900/70 border border-slate-800/90 rounded-xl p-3.5">
                            <div class="text-blue-400 text-lg mb-1"><i class="fa-solid fa-headset"></i></div>
                            <div class="text-xs font-black text-white uppercase">Despacho & PBX</div>
                            <div class="text-[11px] text-slate-400 mt-0.5">Captura automática de llamadas y asignación de móviles.</div>
                        </div>
                        <div class="bg-slate-900/70 border border-slate-800/90 rounded-xl p-3.5">
                            <div class="text-emerald-400 text-lg mb-1"><i class="fa-solid fa-location-crosshairs"></i></div>
                            <div class="text-xs font-black text-white uppercase">GPS & SLA en Vivo</div>
                            <div class="text-[11px] text-slate-400 mt-0.5">Arribo verificado por geocerca y semáforo de tiempos.</div>
                        </div>
                        <div class="bg-slate-900/70 border border-slate-800/90 rounded-xl p-3.5">
                            <div class="text-amber-400 text-lg mb-1"><i class="fa-solid fa-clipboard-check"></i></div>
                            <div class="text-xs font-black text-white uppercase">Actas de Turno</div>
                            <div class="text-[11px] text-slate-400 mt-0.5">Entrega formal de guardia y reporte ejecutivo a Dirección.</div>
                        </div>
                    </div>
                </div>

                <!-- Footer Security Notice -->
                <div class="relative z-10 flex items-center justify-between text-xs text-slate-500 border-t border-slate-800/80 pt-4">
                    <span><i class="fa-solid fa-lock mr-1 text-slate-400"></i> Acceso restringido y auditado • Red Interna Municipal</span>
                    <span class="font-mono text-slate-400">Fono Emergencias: 1454</span>
                </div>
            </div>

            <!-- Right Panel: Authentication Card -->
            <div class="w-full lg:w-5/12 flex flex-col justify-center items-center p-6 sm:p-12 bg-slate-950 relative">
                <!-- Mobile Header -->
                <div class="lg:hidden flex items-center gap-3 mb-8">
                    <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center shadow">
                        <i class="fa-solid fa-shield-halved text-white text-xl"></i>
                    </div>
                    <div>
                        <span class="font-black text-lg tracking-wider text-white block leading-none">BUINSEGURO CAD</span>
                        <span class="text-[10px] text-slate-400 uppercase font-bold">Central de Despacho Municipal</span>
                    </div>
                </div>

                <div class="w-full max-w-md bg-slate-900 border border-slate-800 shadow-2xl rounded-2xl p-6 sm:p-8">
                    {{ $slot }}
                </div>

                <div class="mt-6 text-center text-[11px] text-slate-500">
                    © {{ date('Y') }} Ilustre Municipalidad de Buin • Dirección de Seguridad Pública
                </div>
            </div>
        </div>
    </body>
</html>
