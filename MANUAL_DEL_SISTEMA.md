# Manual del Sistema y Documentación Técnica — BuinSeguro CAD v3.0

**Plataforma Integrada de Central de Comunicaciones, Despacho y Monitoreo Operativo**  
*Dirección de Seguridad Pública • Ilustre Municipalidad de Buin*

---

## 1. Descripción General del Sistema

**BuinSeguro CAD** es el sistema centralizado de despacho asistido por computador (*Computer-Aided Dispatch*) diseñado para la **Dirección de Seguridad Pública de Buin**. Permite gestionar en tiempo real el ciclo completo de las emergencias y requerimientos vecinales:

1. **Captura de Llamadas PBX (Issabel / Asterisk)**: Detección automática en pantalla (*Pop-up* en tiempo real vía WebSockets) cuando suena el anexo del operador, mostrando el historial previo del número llamante y alertas de *pitanzas* reiteradas.
2. **Despacho y Categorización en Dos Etapas (Inicial vs. Operativa)**: Tipificación inicial al recibir el llamado y recategorización confirmada en terreno, con **campos de antecedentes / metadatos dinámicos** configurables por tipo de procedimiento (ej. PPU de vehículos, cantidad de lesionados, especies sustraídas, etc.).
3. **Verificación Dual de Arribo (GPS Móvil + Confirmación Central) y Semáforo SLA**: Reporte georreferenciado desde el móvil en terreno (con cálculo de distancia en metros mediante fórmula geodésica Haversine) y confirmación por parte de la operadora en sala.
4. **Gestión de Turnos Rotativos de Operadores de Sala y Patrullas**: Armado de pautas rotativas (`Turno 1: 08:00 a 14:00`, `Turno 2: 14:00 a 20:00`, `Turno 3: 20:00 a 08:00`) con asignación de **Operadores Telefónicos** (2 por turno) y **Operadores de Cámaras CCTV** (4 a 5 por turno), acta formal de recepción/cierre de guardia y envío automático de reporte estadístico HTML por correo a los Directores.
5. **Contingencias y Eventos Masivos**: Colector rápido de reportes vecinales durante frentes de mal tiempo, cortes masivos de suministro u operativos especiales.
6. **Seguridad y Auditoría Inmutable**: Control de acceso basado en roles (RBAC), aislamiento jurisdiccional por sectores y bitácora de auditoría (`/auditoria`).

---

## 2. Arquitectura Tecnológica

| Componente | Tecnología | Propósito |
| :--- | :--- | :--- |
| **Backend Framework** | Laravel 11 (PHP 8.3) | Lógica de negocio, seguridad RBAC, API Webhook y colas de correo |
| **Frontend Reactivo** | Livewire 3 + Alpine.js + TailwindCSS | Consolas en tiempo real sin recarga de página |
| **Iconografía UI** | FontAwesome 6.5 | Iconografía estándar de sala de despacho CAD |
| **Tiempo Real (WebSockets)** | Laravel Reverb / Echo | Notificación instantánea de llamadas entrantes PBX (`NewCallIncoming`) |
| **Cartografía y GIS** | Google Maps JS API (Places + Visualization) | Geocodificación en Buin, mapa operativo en vivo y mapas de calor (*Heatmaps*) |
| **Telefonía IP** | Asterisk / Issabel PBX + SFTP Streaming | Recepción de eventos de llamada y reproducción segura de grabaciones `.wav` |
| **Base de Datos** | MySQL 8 (Producción) / SQLite (Testing) | Persistencia relacional y campos JSON para metadatos y snapshots estadísticos |

---

## 3. Matriz de Roles y Permisos (RBAC)

El sistema implementa 4 roles institucionales mediante `spatie/laravel-permission`:

| Módulo / Funcionalidad | Administrador | Supervisor | Operador | Inspector (Terreno) |
| :--- | :---: | :---: | :---: | :---: |
| **Sala de Despacho (`/dashboard`)** | Todos los sectores | Todos los sectores | Sectores asignados | Sectores asignados |
| **Mapa Operativo en Vivo (`/mapa-operativo`)** | ✅ | ✅ | ✅ | ✅ |
| **Panel Modo Terreno GPS (`/terreno`)** | ✅ | ✅ | ✅ | ✅ |
| **Turnos de Sala y Entrega (`/turno-sala`)** | ✅ (+ Directores) | ✅ (+ Directores) | ✅ (Operar/Cerrar) | ❌ |
| **Contingencias Masivas (`/eventos-masivos`)** | ✅ (Crear/Operar) | ✅ (Crear/Operar) | ✅ (Operar) | ❌ |
| **Historial Llamadas PBX (`/reportes/llamadas`)** | ✅ (Todos los audios) | ✅ (Todos los audios) | ✅ (Audios de su anexo) | ❌ |
| **Turnos de Flota / Patrullas (`/turnos`)** | ✅ | ✅ | ❌ | ❌ |
| **Estadísticas y Heatmap (`/reportes`)** | ✅ | ✅ | ❌ | ❌ |
| **Auditoría y Seguridad (`/auditoria`)** | ✅ | ✅ | ❌ | ❌ |
| **Mantenedores y Catálogos (`/mantenedores`)** | ✅ | ❌ | ❌ | ❌ |
| **Usuarios, Roles y Anexos (`/usuarios`)** | ✅ | ❌ | ❌ | ❌ |
| **Hitos Geográficos POI (`/hitos`)** | ✅ | ❌ | ❌ | ❌ |

---

## 4. Guía Operativa de Módulos Principales

### 4.1. Sala de Despacho CAD (`/dashboard`)
* **KPIs Superiores y Semáforo SLA**:
  * Filtra con un clic casos **Pendientes**, **En Camino** o **En el Lugar**.
  * Calcula automáticamente el **SLA Promedio de Despacho** y el **SLA Promedio de Arribo** del día.
  * **Semáforo SLA por Caso**:
    * 🟢 **Óptimo**: Arribo en $\le 12$ minutos (o espera $< 5$ min).
    * 🟡 **Precaución**: Arribo entre $13$ y $20$ minutos.
    * 🔴 **Crítico**: Arribo $> 20$ minutos (o sin despacho $> 15$ min).
* **Monitor de Estado de Flota (QAP)**: Permite visualizar y cambiar en un clic el estado de cada móvil (*Disponible QAP*, *En Procedimiento*, *En Colación*, *Punto Fijo*, *En Mantención*).
* **Ingreso de Procedimiento y Metadatos**:
  * Al digitar el teléfono del informante, el sistema consulta automáticamente si registra procedimientos previos o llamadas clasificadas como *Pitanza / Broma*.
  * Al seleccionar el **Tipo de Procedimiento**, se despliegan automáticamente sus **Campos de Antecedentes (Metadatos)** configurados (ej. en *Accidente Vehicular*: Cantidad de vehículos, PPU involucradas, cantidad de lesionados, etc.).

### 4.2. Detalle del Procedimiento, Recategorización y Doble Confirmación de Llegada (`/procedimientos/{id}`)
1. **Confirmación de Llegada desde el Móvil (GPS)**: El inspector en terreno presiona *"Reportar Llegada al Lugar (GPS)"* en `/terreno`. El sistema guarda las coordenadas del móvil y calcula la distancia exacta en metros al punto del incidente.
2. **Confirmación Oficial de Operadora (Central)**: En la consola del caso aparece el aviso de arribo GPS del móvil; la operadora valida la llegada con un clic, cambiando el estado a **En el Lugar** y cerrando el cronómetro de SLA de arribo.
3. **Recategorización Operativa**: Permite actualizar la tipificación según lo constatado por los inspectores en terreno sin perder la **Tipificación Inicial del Llamado**, adaptando dinámicamente los campos de metadatos.
4. **Ficha Oficial Imprimible / PDF (`/procedimientos/{id}/ficha`)**: Genera el expediente completo con tiempos SLA, bitácora cronológica, metadatos y tripulación interviniente.

### 4.3. Turnos Rotativos de Operadores de Sala y Reporte a Directores (`/turno-sala`)
Funciona bajo el mismo esquema de pauta diaria que los turnos de patrullas:
* **Bloques Rotativos Preconfigurados**:
  * **Turno 1 (`08:00 a 14:00 hrs`)**: Dotación sugerida de **2 Operadores Telefónicos** y **4 Operadores de Cámaras (CCTV)**.
  * **Turno 2 (`14:00 a 20:00 hrs`)**: Dotación sugerida de **2 Operadores Telefónicos** y **4 Operadores de Cámaras (CCTV)**.
  * **Turno 3 (`20:00 a 08:00 hrs`)**: Dotación sugerida de **2 Operadores Telefónicos** y **5 Operadores de Cámaras (CCTV)**.
* **Herramientas de Armado Rápido**:
  * Botón **Plantilla 2+4 / 2+5**: Genera automáticamente las filas de puestos telefónicos y de cámaras listas para seleccionar a los funcionarios.
  * Botón **Rotar**: Clona la dotación del turno anterior para rotar equipos entre bloques horarios.
* **Cierre de Guardia y Email Automático a Directores**:
  * Al cerrar el turno e indicar el operador entrante, el sistema compila las estadísticas de la ventana horaria del turno y envía automáticamente el correo ejecutivo HTML (`ShiftClosedReportMail`) a todos los directores activos en la nómina.

### 4.4. Mantenedores y Catálogos con Validación (`/mantenedores`)
Todos los catálogos, etiquetas, bloques de turno y estados cuentan con su propio CRUD validado (unicidad, obligatoriedad, orden, activación/desactivación y protección de integridad referencial):
* **Catálogos Base**: *Sectores / Jurisdicciones*, *Tipos de Procedimiento (+ Constructor de Metadatos)*, *Dispositivos / Móviles*, *Orígenes de Ingreso*, *Categorías de Reportes de Eventos*.
* **Catálogos Operativos (`system_catalog_items`)**:
  * *Horarios Turnos Operadores (Sala)* (incluye horario inicio/término y dotación mínima de operadores telefónicos y de cámaras).
  * *Puestos / Funciones en Sala CAD* (*Operador Telefónico*, *Operador de Cámaras CCTV*, *Despachador Radial*, *Jefe de Sala*).
  * *Horarios Turnos Patrullas (Flota)* y *Roles de Tripulación (Patrullas)*.
  * *Tipificaciones Llamadas PBX*, *Acciones de Bitácora de Casos*, *Estados Operativos de Flota (QAP)* y *Tipos de Contingencia / Eventos Masivos*.

---

## 5. Integración Webhook Asterisk / Issabel PBX

* **Endpoint**: `POST /v1/asterisk/webhook` (protegido por *rate-limiting* `throttle:120,1` y validación de token `X-Asterisk-Token` si se define `ASTERISK_WEBHOOK_TOKEN` en `.env`).
* **Parámetros esperados**:
  * `id`: UniqueID de la llamada en Asterisk (obligatorio).
  * `numero`: Número telefónico del llamante (`caller_id`).
  * `extension`: Anexo de destino en la central (ej. `101`).
  * `status`: Estado (`ringing`, `answered`, `hangup`).
  * `recording_path`: Ruta relativa del archivo de audio en el servidor PBX.

---

## 6. Ejecución de Pruebas Unitarias y de Seguridad

El proyecto incluye pruebas automatizadas en `tests/Unit/ProcedureCalculationsTest.php` y `tests/Feature/CadSecurityAndAuditTest.php`:

```bash
php vendor/phpunit/phpunit/phpunit --testsuite Unit
php vendor/phpunit/phpunit/phpunit --filter CadSecurityAndAuditTest
```
