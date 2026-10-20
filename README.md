# BuinSeguro MiniCAD (`v1.0.0`)

Sistema **CAD (Computer-Aided Dispatch)** de Central de Despacho, Monitoreo de Procedimientos, Turnos Rotativos y Gestión de Contingencias para el Departamento de Seguridad Pública Municipal de Buin.

## Características Principales (`v1.0.0`)

- **Sala de Despacho CAD y Telefonía PBX (Asterisk / Issabel)**:
  - Pop-up automático de llamada entrante con historial previo del número y clasificación rápida.
  - Doble tipificación operativa (*Tipificación de Entrada* vs *Tipificación Final*) con formularios dinámicos de metadatos por categoría.
  - Semáforo SLA en tiempo real (Verde $\le 10$ min, Amarillo $11\text{--}20$ min, Rojo $> 20$ min).
- **Confirmación Dual de Arribo y Georreferenciación**:
  - Confirmación en terreno con GPS (`lat/lng`) por el inspector móvil y validación desde Sala por el operador.
- **Turnos Rotativos de Sala y Patrullas**:
  - Armado de pauta rotativa de operadores (`Turno 1: 08:00 a 14:00`, `Turno 2: 14:00 a 20:00`, `Turno 3: 20:00 a 08:00`) con control de dotación de **Operadores Telefónicos** y **Operadores de Cámaras (CCTV)**.
  - Cierre y entrega de turno con envío automático de reporte estadístico HTML por correo a Directores/Jefaturas.
- **Eventos Masivos y Contingencias**:
  - Agrupación de reportes vecinales bajo un incidente mayor (ej. frente de mal tiempo, corte de suministro).
- **Centro Único de Mantenedores y Auditoría**:
  - CRUDs completos con validaciones para todos los catálogos, etiquetas, bloques horarios, estados y roles.
  - Trazabilidad completa de seguridad y acciones críticas en `audit_logs`.

## Documentación

Consulta el manual completo en [`MANUAL_DEL_SISTEMA.md`](MANUAL_DEL_SISTEMA.md) o dentro de la aplicación en la ruta `/documentacion`.
