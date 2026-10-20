<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Ejecutivo de Cierre de Turno #{{ $shift->id }} - BuinSeguro CAD</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b;">

    @if(request()->routeIs('shifts.email-preview'))
        <div style="max-width: 780px; margin: 16px auto 0; background: #0f172a; color: #ffffff; padding: 12px 20px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
            <span><strong>Vista Previa de Plantilla de Email a Directores</strong> · Turno #{{ $shift->id }}</span>
            <div>
                <a href="{{ route('shifts.manage') }}" style="color: #93c5fd; text-decoration: none; margin-right: 14px; font-weight: bold;">&larr; Volver a Gestión de Turnos</a>
                <button onclick="window.print()" style="background: #2563eb; color: #ffffff; border: none; padding: 6px 14px; border-radius: 6px; font-weight: bold; cursor: pointer;">Imprimir / PDF</button>
            </div>
        </div>
    @endif

    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f1f5f9; padding: 24px 12px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width: 780px; background-color: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #cbd5e1; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);">
                    
                    {{-- ENCABEZADO INSTITUCIONAL --}}
                    <tr>
                        <td style="background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%); padding: 26px 32px; color: #ffffff;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td>
                                        <div style="font-size: 11px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase; color: #60a5fa; margin-bottom: 4px;">
                                            ILUSTRE MUNICIPALIDAD DE BUIN · DIRECCIÓN DE SEGURIDAD PÚBLICA
                                        </div>
                                        <h1 style="margin: 0; font-size: 22px; font-weight: 900; letter-spacing: 0.3px; color: #ffffff;">
                                            REPORTE EJECUTIVO DE CIERRE DE TURNO (CAD)
                                        </h1>
                                        <p style="margin: 6px 0 0; font-size: 13px; color: #cbd5e1;">
                                            Turno: <strong style="color: #ffffff;">{{ $shift->shift_type }}</strong> · Acta Folio <strong style="color: #93c5fd;">#{{ str_pad($shift->id, 5, '0', STR_PAD_LEFT) }}</strong>
                                        </p>
                                    </td>
                                    <td align="right" valign="top">
                                        <div style="background-color: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.2); padding: 8px 14px; border-radius: 8px; text-align: right;">
                                            <div style="font-size: 10px; text-transform: uppercase; color: #cbd5e1; font-weight: 700;">Cierre de Turno</div>
                                            <div style="font-size: 14px; font-weight: 800; color: #ffffff;">
                                                {{ ($shift->closed_at ?? now())->format('d/m/Y H:i') }} hrs
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- SECCIÓN 1: DATOS DE ENTREGA Y RECEPCIÓN DE SALA --}}
                    <tr>
                        <td style="padding: 24px 32px 12px;">
                            <div style="font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.8px; color: #334155; border-bottom: 2px solid #e2e8f0; padding-bottom: 6px; margin-bottom: 14px;">
                                1. Antecedentes de Apertura, Entrega y Recepción de Sala
                            </div>
                            <table width="100%" cellpadding="8" cellspacing="0" border="0" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px;">
                                <tr>
                                    <td width="50%" style="border-bottom: 1px solid #e2e8f0;">
                                        <span style="color: #64748b; font-weight: 600;">Inicio de Turno:</span><br>
                                        <strong style="color: #0f172a;">{{ $shift->started_at->format('d/m/Y H:i') }} hrs</strong>
                                    </td>
                                    <td width="50%" style="border-bottom: 1px solid #e2e8f0;">
                                        <span style="color: #64748b; font-weight: 600;">Término de Turno:</span><br>
                                        <strong style="color: #0f172a;">{{ ($shift->closed_at ?? now())->format('d/m/Y H:i') }} hrs</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="border-bottom: 1px solid #e2e8f0;">
                                        <span style="color: #64748b; font-weight: 600;">Responsable Saliente (Entrega):</span><br>
                                        <strong style="color: #0f172a;">{{ $shift->closedBy->name ?? ($shift->openedBy->name ?? 'N/A') }}</strong>
                                    </td>
                                    <td style="border-bottom: 1px solid #e2e8f0;">
                                        <span style="color: #64748b; font-weight: 600;">Responsable Entrante (Recibe):</span><br>
                                        <strong style="color: #1d4ed8;">{{ $shift->receivedBy->name ?? 'Por asignar en apertura entrante' }}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <span style="color: #64748b; font-weight: 600;">Operadores en Consola durante el Turno:</span>
                                        <strong style="color: #0f172a;">
                                            {{ !empty($shift->operators_on_duty) ? implode(', ', $shift->operators_on_duty) : ($shift->openedBy->name ?? 'Sin detalle') }}
                                        </strong>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- SECCIÓN 2: KPIS ESTADÍSTICOS DEL TURNO --}}
                    <tr>
                        <td style="padding: 12px 32px;">
                            <div style="font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.8px; color: #334155; border-bottom: 2px solid #e2e8f0; padding-bottom: 6px; margin-bottom: 14px;">
                                2. Resumen Estadístico e Indicadores SLA del Turno
                            </div>
                            <table width="100%" cellpadding="0" cellspacing="8" border="0" style="margin: 0 -8px;">
                                <tr>
                                    <td width="25%" style="background-color: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 14px; text-align: center;">
                                        <div style="font-size: 10px; font-weight: 800; text-transform: uppercase; color: #1e40af;">Casos Ingresados</div>
                                        <div style="font-size: 26px; font-weight: 900; color: #1d4ed8; margin-top: 4px;">{{ $stats['total_procedures'] ?? 0 }}</div>
                                        <div style="font-size: 11px; color: #3b82f6;">Cerrados: {{ $stats['closed_procedures'] ?? 0 }}</div>
                                    </td>
                                    <td width="25%" style="background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 14px; text-align: center;">
                                        <div style="font-size: 10px; font-weight: 800; text-transform: uppercase; color: #991b1b;">En Traspaso (Abiertos)</div>
                                        <div style="font-size: 26px; font-weight: 900; color: #dc2626; margin-top: 4px;">{{ $stats['open_handover_count'] ?? 0 }}</div>
                                        <div style="font-size: 11px; color: #ef4444;">Para seguimiento</div>
                                    </td>
                                    <td width="25%" style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 14px; text-align: center;">
                                        <div style="font-size: 10px; font-weight: 800; text-transform: uppercase; color: #166534;">SLA Arribo Prom.</div>
                                        <div style="font-size: 24px; font-weight: 900; color: #15803d; margin-top: 4px;">
                                            {{ !is_null($stats['avg_arrival_minutes'] ?? null) ? $stats['avg_arrival_minutes'] . ' min' : 'S/D' }}
                                        </div>
                                        <div style="font-size: 11px; color: #16a34a;">
                                            Despacho: {{ !is_null($stats['avg_dispatch_minutes'] ?? null) ? $stats['avg_dispatch_minutes'] . 'm' : 'S/D' }}
                                        </div>
                                    </td>
                                    <td width="25%" style="background-color: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 14px; text-align: center;">
                                        <div style="font-size: 10px; font-weight: 800; text-transform: uppercase; color: #334155;">Llamadas PBX</div>
                                        <div style="font-size: 26px; font-weight: 900; color: #0f172a; margin-top: 4px;">{{ $stats['total_calls'] ?? 0 }}</div>
                                        <div style="font-size: 11px; color: #64748b;">Pitanzas: {{ $stats['prank_calls'] ?? 0 }}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- SECCIÓN 3: DISTRIBUCIÓN POR SECTOR Y TIPIFICACIÓN --}}
                    <tr>
                        <td style="padding: 12px 32px;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td width="48%" valign="top" style="padding-right: 12px;">
                                        <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #475569; margin-bottom: 8px;">
                                            Distribución por Sector
                                        </div>
                                        <table width="100%" cellpadding="6" cellspacing="0" border="0" style="border: 1px solid #e2e8f0; border-radius: 6px; font-size: 12px;">
                                            @forelse(($stats['by_sector'] ?? []) as $secName => $count)
                                                <tr>
                                                    <td style="border-bottom: 1px solid #f1f5f9; font-weight: 600;">{{ $secName }}</td>
                                                    <td align="right" style="border-bottom: 1px solid #f1f5f9; font-weight: 800; color: #1d4ed8;">{{ $count }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="2" style="color: #94a3b8; text-align: center; padding: 10px;">Sin procedimientos en este periodo</td>
                                                </tr>
                                            @endforelse
                                        </table>
                                    </td>
                                    <td width="48%" valign="top" style="padding-left: 12px;">
                                        <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #475569; margin-bottom: 8px;">
                                            Principales Tipificaciones del Turno
                                        </div>
                                        <table width="100%" cellpadding="6" cellspacing="0" border="0" style="border: 1px solid #e2e8f0; border-radius: 6px; font-size: 12px;">
                                            @forelse(($stats['by_type'] ?? []) as $typeName => $count)
                                                <tr>
                                                    <td style="border-bottom: 1px solid #f1f5f9; font-weight: 600;">{{ $typeName }}</td>
                                                    <td align="right" style="border-bottom: 1px solid #f1f5f9; font-weight: 800; color: #0f172a;">{{ $count }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="2" style="color: #94a3b8; text-align: center; padding: 10px;">Sin procedimientos en este periodo</td>
                                                </tr>
                                            @endforelse
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- SECCIÓN 4: ESTADO DE SISTEMAS DE SALA Y NOVEDADES / CONSIGNAS --}}
                    <tr>
                        <td style="padding: 12px 32px;">
                            <div style="font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.8px; color: #334155; border-bottom: 2px solid #e2e8f0; padding-bottom: 6px; margin-bottom: 10px;">
                                3. Novedades de Sala, Equipos y Consignas de Traspaso
                            </div>

                            @if(!empty($shift->equipment_status))
                                <div style="margin-bottom: 12px; font-size: 12px; background-color: #f8fafc; padding: 10px 14px; border-radius: 6px; border: 1px solid #e2e8f0;">
                                    <strong>Estado Técnico de Sala:</strong>
                                    Radio VHF: <strong>{{ $shift->equipment_status['radio_vhf'] ?? 'Operativo' }}</strong> ·
                                    Central PBX: <strong>{{ $shift->equipment_status['central_pbx'] ?? 'Operativo' }}</strong> ·
                                    Cámaras CCTV: <strong>{{ $shift->equipment_status['camaras_cctv'] ?? 'Operativo' }}</strong> ·
                                    GPS Flota: <strong>{{ $shift->equipment_status['gps_flota'] ?? 'Operativo' }}</strong>
                                </div>
                            @endif

                            @if($shift->shift_log_notes)
                                <div style="margin-bottom: 10px; background-color: #f8fafc; border-left: 4px solid #3b82f6; padding: 10px 14px; font-size: 13px;">
                                    <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #1d4ed8; margin-bottom: 4px;">Resumen de Novedades del Turno:</div>
                                    <div style="white-space: pre-line; color: #334155;">{{ $shift->shift_log_notes }}</div>
                                </div>
                            @endif

                            @if($shift->handover_notes)
                                <div style="margin-bottom: 10px; background-color: #fffbeb; border-left: 4px solid #f59e0b; padding: 10px 14px; font-size: 13px;">
                                    <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #b45309; margin-bottom: 4px;">Consignas Pendientes para el Turno Entrante:</div>
                                    <div style="white-space: pre-line; color: #78350f;">{{ $shift->handover_notes }}</div>
                                </div>
                            @endif
                        </td>
                    </tr>

                    {{-- SECCIÓN 5: CASOS ABIERTOS TRASPASADOS AL TURNO ENTRANTE --}}
                    @if(!empty($stats['open_handover_cases']))
                        <tr>
                            <td style="padding: 12px 32px 24px;">
                                <div style="font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.8px; color: #b91c1c; border-bottom: 2px solid #fecaca; padding-bottom: 6px; margin-bottom: 10px;">
                                    4. Procedimientos Activos en Traspaso ({{ count($stats['open_handover_cases']) }})
                                </div>
                                <table width="100%" cellpadding="7" cellspacing="0" border="0" style="border: 1px solid #e2e8f0; border-radius: 6px; font-size: 12px;">
                                    <thead>
                                        <tr style="background-color: #1e293b; color: #ffffff; text-align: left;">
                                            <th>Folio</th>
                                            <th>Hora</th>
                                            <th>Sector / Dirección</th>
                                            <th>Tipificación</th>
                                            <th>Móvil</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($stats['open_handover_cases'] as $oc)
                                            <tr>
                                                <td style="border-bottom: 1px solid #e2e8f0; font-weight: 800;">#{{ $oc['id'] }}</td>
                                                <td style="border-bottom: 1px solid #e2e8f0;">{{ $oc['time'] }}</td>
                                                <td style="border-bottom: 1px solid #e2e8f0;"><strong>{{ $oc['sector'] }}</strong> — {{ $oc['address'] }}</td>
                                                <td style="border-bottom: 1px solid #e2e8f0;">{{ $oc['type'] }}</td>
                                                <td style="border-bottom: 1px solid #e2e8f0;">{{ $oc['device'] }}</td>
                                                <td style="border-bottom: 1px solid #e2e8f0; font-weight: 700; color: #b45309;">{{ $oc['status'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    @endif

                    {{-- PIE DEL CORREO --}}
                    <tr>
                        <td style="background-color: #0f172a; color: #94a3b8; padding: 18px 32px; font-size: 11px; text-align: center;">
                            Reporte automático generado por <strong style="color: #ffffff;">BuinSeguro CAD</strong> — Central de Despacho y Monitoreo de Procedimientos.<br>
                            Ilustre Municipalidad de Buin · Dirección de Seguridad Pública.
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
