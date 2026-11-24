<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido a Buin Seguro MiniCAD</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f5f8; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f4f5f8; padding: 28px 12px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width: 640px; background-color: #ffffff; border-radius: 14px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 6px 18px rgba(15, 23, 42, 0.08);">

                    {{-- ENCABEZADO INSTITUCIONAL --}}
                    <tr>
                        <td style="background: linear-gradient(135deg, #1d1f59 0%, #2c2e83 100%); padding: 28px 32px; color: #ffffff;">
                            <div style="font-size: 11px; font-weight: 800; letter-spacing: 1.4px; text-transform: uppercase; color: #c5df81; margin-bottom: 6px;">
                                ILUSTRE MUNICIPALIDAD DE BUIN · DIRECCIÓN DE SEGURIDAD PÚBLICA
                            </div>
                            <h1 style="margin: 0; font-size: 22px; font-weight: 900; color: #ffffff; letter-spacing: 0.2px;">
                                {{ $isPasswordReset ? 'Actualización de Acceso — Buin Seguro MiniCAD' : '¡Bienvenido/a a Buin Seguro MiniCAD!' }}
                            </h1>
                            <p style="margin: 8px 0 0; font-size: 13px; color: #d5d8f4;">
                                Central de Despacho, Monitoreo Operativo y Gestión en Terreno
                            </p>
                        </td>
                    </tr>

                    {{-- SALUDO --}}
                    <tr>
                        <td style="padding: 26px 32px 10px;">
                            <p style="margin: 0 0 12px; font-size: 15px; line-height: 1.6; color: #0f172a;">
                                Hola <strong>{{ $user->name }}</strong>,
                            </p>
                            <p style="margin: 0; font-size: 14px; line-height: 1.6; color: #475569;">
                                @if($isPasswordReset)
                                    Se han actualizado tus credenciales de acceso a la plataforma operativa <strong>Buin Seguro MiniCAD</strong>. A continuación encontrarás el detalle de tu cuenta:
                                @else
                                    Ya se encuentra habilitada tu cuenta en la plataforma operativa <strong>Buin Seguro MiniCAD</strong>. A continuación encontrarás tus datos de ingreso y perfil asignado:
                                @endif
                            </p>
                        </td>
                    </tr>

                    {{-- TARJETA DE CREDENCIALES Y PERFIL --}}
                    <tr>
                        <td style="padding: 16px 32px;">
                            <table width="100%" cellpadding="10" cellspacing="0" border="0" style="background-color: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px;">
                                <tr>
                                    <td width="40%" style="color: #64748b; font-weight: 700; border-bottom: 1px solid #e2e8f0;">Enlace de ingreso</td>
                                    <td style="color: #2c2e83; font-weight: 800; border-bottom: 1px solid #e2e8f0;">
                                        <a href="{{ $loginUrl }}" style="color: #2c2e83; text-decoration: none;">{{ $loginUrl }}</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="color: #64748b; font-weight: 700; border-bottom: 1px solid #e2e8f0;">Usuario / Correo</td>
                                    <td style="color: #0f172a; font-weight: 800; font-family: monospace; border-bottom: 1px solid #e2e8f0;">{{ $user->email }}</td>
                                </tr>
                                @if($temporaryPassword)
                                    <tr>
                                        <td style="color: #64748b; font-weight: 700; border-bottom: 1px solid #e2e8f0;">Contraseña temporal</td>
                                        <td style="border-bottom: 1px solid #e2e8f0;">
                                            <span style="display: inline-block; background-color: #eef0fb; color: #1d1f59; border: 1px solid #c7d2fe; padding: 4px 10px; border-radius: 6px; font-family: monospace; font-size: 14px; font-weight: 900; letter-spacing: 0.5px;">
                                                {{ $temporaryPassword }}
                                            </span>
                                        </td>
                                    </tr>
                                @endif
                                <tr>
                                    <td style="color: #64748b; font-weight: 700; border-bottom: 1px solid #e2e8f0;">Rol / Perfil</td>
                                    <td style="color: #0f172a; font-weight: 800; border-bottom: 1px solid #e2e8f0;">{{ $roleName }}</td>
                                </tr>
                                <tr>
                                    <td style="color: #64748b; font-weight: 700; border-bottom: 1px solid #e2e8f0;">Anexo telefónico</td>
                                    <td style="color: #0f172a; font-weight: 700; border-bottom: 1px solid #e2e8f0;">{{ $user->extension ?: 'No aplica / Sin anexo' }}</td>
                                </tr>
                                <tr>
                                    <td style="color: #64748b; font-weight: 700;">Sectores / Cobertura</td>
                                    <td style="color: #0f172a; font-weight: 700;">
                                        @if(in_array($roleName, ['Administrador', 'Supervisor'], true))
                                            Acceso global a todos los sectores
                                        @elseif($roleName === 'Inspector')
                                            Operación en terreno según móvil asignado en turno
                                        @elseif(! empty($sectorNames))
                                            {{ implode(', ', $sectorNames) }}
                                        @else
                                            Pendiente de asignación por Central
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- BOTÓN DE ACCESO --}}
                    <tr>
                        <td align="center" style="padding: 8px 32px 20px;">
                            <a href="{{ $loginUrl }}"
                               style="display: inline-block; background-color: #94c11e; color: #08080a; text-decoration: none; font-weight: 900; font-size: 14px; padding: 12px 28px; border-radius: 8px;">
                                Ingresar a Buin Seguro MiniCAD &rarr;
                            </a>
                        </td>
                    </tr>

                    {{-- PRIMEROS PASOS --}}
                    <tr>
                        <td style="padding: 0 32px 24px;">
                            <div style="background-color: #f5fae9; border: 1px solid #c5df81; border-radius: 10px; padding: 16px 20px;">
                                <div style="font-size: 12px; font-weight: 900; text-transform: uppercase; color: #5b7812; margin-bottom: 8px;">
                                    Primeros pasos recomendados
                                </div>
                                <ol style="margin: 0; padding-left: 18px; font-size: 13px; line-height: 1.65; color: #334155;">
                                    <li>Ingresa al enlace con tu correo institucional y tu contraseña{{ $temporaryPassword ? ' temporal' : '' }}.</li>
                                    <li>En el menú superior derecho (<strong>Tu Perfil &rarr; Perfil</strong>), cambia tu contraseña por una personal y segura.</li>
                                    @if($roleName === 'Inspector')
                                        <li>Desde tu teléfono, abre el menú del navegador y elige <strong>«Agregar a pantalla de inicio»</strong> para instalar la <strong>Consola Móvil PWA</strong> de terreno.</li>
                                    @else
                                        <li>Desde tu menú de perfil podrás ajustar el tema visual (Claro/Oscuro) y las alertas sonoras de llamados.</li>
                                    @endif
                                </ol>
                            </div>
                        </td>
                    </tr>

                    {{-- PIE --}}
                    <tr>
                        <td style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 16px 32px; text-align: center; font-size: 11px; color: #64748b;">
                            Ante cualquier inconveniente de acceso o permisos, comunícate con la administración de Central Buin Seguro.<br>
                            <strong>Dirección de Seguridad Pública — Ilustre Municipalidad de Buin</strong>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
