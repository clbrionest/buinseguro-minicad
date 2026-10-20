<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      data-theme="light"
      data-theme-preference="system"
      data-authenticated="false">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>BuinSeguro CAD — Central de Comunicaciones y Despacho</title>

        <!-- Script Anti-Destello (FOUC) — Buin Seguro CAD Design System v1.0 -->
        <script>
            (() => {
                try {
                    const stored = localStorage.getItem('cad-theme');
                    const preference = ['light', 'dark', 'system'].includes(stored) ? stored : 'system';
                    const isDark = preference === 'dark' || (preference === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                    document.documentElement.dataset.theme = isDark ? 'dark' : 'light';
                    document.documentElement.dataset.themePreference = preference;
                } catch (_) {
                    document.documentElement.dataset.theme = 'light';
                }
            })();
        </script>

        <!-- Fonts & Icons -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800|jetbrains-mono:400,500,600,700&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-ui-fg antialiased bg-ui-app selection:bg-brand-cobalt selection:text-brand-white">
        <div class="min-h-screen flex flex-col lg:flex-row">
            <!-- Left Panel: Institutional Command Center Branding (#2C2E83 Cobalt + #94C11E Lime) -->
            <div class="hidden lg:flex lg:w-7/12 relative bg-ui-sidebar text-brand-white p-12 flex-col justify-between border-r border-white/10 overflow-hidden">
                <!-- Subtle institutional grid background -->
                <div class="absolute inset-0 opacity-10 pointer-events-none" style="background-image: radial-gradient(#94C11E 1px, transparent 1px); background-size: 28px 28px;"></div>

                <!-- Header Brand -->
                <div class="relative z-10 flex items-center justify-between">
                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-xl bg-brand-lime flex items-center justify-center shadow-md border border-white/20">
                            <i class="fa-solid fa-shield-halved text-brand-black text-2xl" aria-hidden="true"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-black text-xl tracking-wider text-brand-white">BUIN SEGURO</span>
                                <span class="bg-brand-lime text-brand-black text-[10px] font-black uppercase px-2 py-0.5 rounded">CAD v1.0</span>
                            </div>
                            <p class="text-xs text-brand-cobalt-light font-semibold uppercase tracking-widest">Dirección de Seguridad Pública • I. Municipalidad de Buin</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 bg-black/25 border border-white/15 px-3 py-1.5 rounded-full text-xs font-bold text-brand-lime-mid">
                        <span class="w-2 h-2 rounded-full bg-brand-lime"></span>
                        <span>SISTEMA OPERATIVO</span>
                    </div>
                </div>

                <!-- Main Hero Info -->
                <div class="relative z-10 my-auto max-w-xl">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-md bg-white/10 border border-white/15 text-brand-lime-light text-xs font-bold uppercase tracking-wider mb-4">
                        <i class="fa-solid fa-satellite-dish" aria-hidden="true"></i> Central de Comunicaciones y Despacho
                    </div>
                    <h1 class="text-3xl xl:text-4xl font-extrabold text-brand-white tracking-tight leading-tight">
                        Plataforma Institucional de Monitoreo, Despacho y Gestión Operativa
                    </h1>
                    <p class="mt-4 text-brand-cobalt-light text-sm leading-relaxed">
                        Sistema oficial para operadores telefónicos, central CCTV, supervisores de sala, inspectores en terreno y dirección ejecutiva. Integrado con telefonía IP Asterisk PBX, georreferenciación de flota QAP y control de niveles de servicio (SLA).
                    </p>

                    <!-- Feature Cards -->
                    <div class="grid grid-cols-3 gap-3 mt-8">
                        <div class="bg-black/20 border border-white/15 rounded-lg p-3.5">
                            <div class="text-brand-lime text-lg mb-1"><i class="fa-solid fa-headset" aria-hidden="true"></i></div>
                            <div class="text-xs font-bold text-brand-white uppercase">Despacho & PBX</div>
                            <div class="text-[11px] text-brand-cobalt-light mt-0.5">Captura rápida de llamadas y asignación de móviles.</div>
                        </div>
                        <div class="bg-black/20 border border-white/15 rounded-lg p-3.5">
                            <div class="text-brand-lime text-lg mb-1"><i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i></div>
                            <div class="text-xs font-bold text-brand-white uppercase">GPS & SLA en Vivo</div>
                            <div class="text-[11px] text-brand-cobalt-light mt-0.5">Arribo verificado por geocerca y control de tiempos.</div>
                        </div>
                        <div class="bg-black/20 border border-white/15 rounded-lg p-3.5">
                            <div class="text-brand-lime text-lg mb-1"><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i></div>
                            <div class="text-xs font-bold text-brand-white uppercase">Actas de Turno</div>
                            <div class="text-[11px] text-brand-cobalt-light mt-0.5">Entrega formal de guardia y reporte ejecutivo a Dirección.</div>
                        </div>
                    </div>
                </div>

                <!-- Footer Security Notice -->
                <div class="relative z-10 flex items-center justify-between text-xs text-brand-cobalt-light border-t border-white/15 pt-4">
                    <span><i class="fa-solid fa-lock mr-1" aria-hidden="true"></i> Acceso restringido y auditado • Red Interna Municipal</span>
                    <span class="font-mono cad-tabular font-bold text-brand-lime-light">Fono Emergencias: 1452</span>
                </div>
            </div>

            <!-- Right Panel: Authentication Card (Adapts to Light / Dark / System Theme) -->
            <div class="w-full lg:w-5/12 flex flex-col justify-between p-6 sm:p-10 bg-ui-app text-ui-fg relative">
                <!-- Top Bar with Mobile Brand & Theme Switcher -->
                <div class="flex items-center justify-between w-full max-w-md mx-auto">
                    <div class="lg:hidden flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-lg bg-brand-cobalt flex items-center justify-center shadow-xs">
                            <i class="fa-solid fa-shield-halved text-brand-lime text-lg" aria-hidden="true"></i>
                        </div>
                        <div>
                            <span class="font-black text-sm tracking-wider text-ui-fg block leading-none">BUIN SEGURO CAD</span>
                            <span class="text-[10px] text-ui-muted uppercase font-bold">Central de Despacho</span>
                        </div>
                    </div>
                    <div class="ml-auto">
                        <x-cad.theme-switcher />
                    </div>
                </div>

                <div class="w-full max-w-md mx-auto my-auto py-6">
                    <div class="bg-ui-card border border-ui-border shadow-lg rounded-xl p-6 sm:p-8 text-ui-fg">
                        {{ $slot }}
                    </div>
                </div>

                <div class="text-center text-[11px] text-ui-muted">
                    © {{ date('Y') }} Ilustre Municipalidad de Buin • Dirección de Seguridad Pública
                </div>
            </div>
        </div>
    </body>
</html>
