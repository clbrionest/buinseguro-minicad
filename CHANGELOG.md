# Changelog — Buin Seguro MiniCAD

Todos los cambios relevantes de este proyecto se documentan automáticamente en este archivo.
El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/) y el proyecto adhiere a [Semantic Versioning (SemVer 2.0.0)](https://semver.org/lang/es/).

## [v1.4.0] - 2026-11-17 — `PR #15`, `PR #14`, `PR #13`, `PR #12`, `PR #11`, `PR #10`, `PR #9`, `PR #8`
**Actualización Automática desde rama principal**

### ✨ Nuevas Funcionalidades (Features)
- **[patrullaje]** PR #14 (feat/patrullaje): fix: eliminar redeclaracion duplicada de
- **[patrullaje]** PR #13 (feat/patrullaje): patrullaje preventivo v1
- **[mapa-operativo]** PR #12 (feat/mapa-operativo): feat: mapa-operacional

### 🐛 Correcciones (Bug Fixes)
- **[fix]** Eliminar redeclaracion duplicada de validatePatrolCheckpoint y openModalWithPatrol
- **[ui-fixes]** PR #11 (fix/ui-fixes): ui fixes - vistas y categorías
- **[pbx-webservice]** PR #10 (fix/pbx-webservice): fix: modal para creación de caso + creación de caso
- **[pbx-webservice]** PR #9 (fix/pbx-webservice): se corrige el log channel y las fachadas respectivas
- **[pbx-webservice]** PR #8 (fix/pbx-webservice): correcciones en conexion a pbx y laravel reverb

### 🔧 Mantenimiento y Arquitectura (Chores)
- **[semver]** PR #15 (chore/semver): semver y changelog
- **[sistema]** Semver

## [v1.3.0] - 2026-11-17 — `feat/patrullaje`, `feat/mapa-operativo`, `chore/semver`
**Mapa Operativo Táctico, Patrullajes Preventivos y Versionado SemVer**

### ✨ Nuevas Funcionalidades (Features)
- **[patrullaje]** Separación de Patrullajes Preventivos con validación de hitos por GPS, inicio de procedimientos vinculados e informe oficial imprimible.
- **[mapa-operativo]** Rediseño del Mapa Operativo en Vivo a pantalla completa con HUD táctico, marcadores vectoriales SVG y selector de skins cartográficos.
- **[semver]** Implementación de Semantic Versioning (SemVer) y generación automática de Changelog sincronizado con la rama main.

## [v1.2.0] - 2026-11-17 — `fix/ui-fixes`, `fix/pbx-webservice`
**Integración Telefónica SFTP, Vinculación de Casos y Auditoría Dual**

### ✨ Nuevas Funcionalidades (Features)
- **[telefonia]** Modal global para creación de procedimientos desde alertas de llamadas entrantes y vinculación de grabaciones desde el historial.
- **[auditoria]** Registro dual de auditoría y errores del sistema en base de datos y canal dedicado de logs.

### 🐛 Correcciones (Bug Fixes)
- **[sftp-audio]** Normalización de directorio raíz SFTP (/monitor) para grabaciones y timbre sintetizado Web Audio API con eventos Laravel Reverb.
- **[ui]** Estandarización de terminología operativa en vistas y actualización de números de emergencia (1426 y 800 00 2015).

## [v1.1.2] - 2026-11-16 — `PR #7`, `PR #6`
**Automatización de Despliegue y Matriz PHP en CI**

### 🐛 Correcciones (Bug Fixes)
- **[ci-vite]** PR #6 (fix/ci-vite-manifest): Consolidación de compilación de recursos Vite previo a pruebas automatizadas.

### 🔧 Mantenimiento y Arquitectura (Chores)
- **[ci-deploy]** PR #7 (chore/auto-deploy): Configuración de flujo de auto-deploy y ajuste de versiones de PHP (8.3 y 8.4) en la matriz de integración continua.

## [v1.1.1] - 2026-10-23 — `PR #5`, `PR #4`, `PR #3`, `PR #1`
**Integración Continua GitHub Actions y Entorno Docker**

### 🐛 Correcciones (Bug Fixes)
- **[ci]** PR #5 (fix/ci-vite-manifest): Compilación de manifiesto Vite antes de la ejecución de PHPUnit en GitHub Actions.

### ⚙️ Integración Continua (CI/CD)
- **[workflows]** PR #4 (chore/github-actions): Configuración de validación automática de Laravel, Composer y Vite en CI.

### 🔧 Mantenimiento y Arquitectura (Chores)
- **[ia]** PR #3 (chore/configuracion-ia): Incorporación de guías y reglas de arquitectura para desarrollo en Laravel.
- **[docker]** PR #1 (chore/entorno-desarrollo): Configuración de contenedores Docker Compose (PHP 8.4 y MySQL 8.4) para desarrollo.

## [v1.1.0] - 2026-10-20 — `feat/design-system`, `feat/shifts`
**Buin Seguro CAD Design System v1.0 y Gestión de Sala**

### ✨ Nuevas Funcionalidades (Features)
- **[branding]** Incorporación de escudo institucional en barra de navegación, acceso y vistas públicas.
- **[ui]** Implementación de Buin Seguro CAD Design System v1.0 con soporte de temas Claro, Oscuro y Sistema.
- **[turnos]** Mantenedor de equipamiento de sala con soporte de baja lógica y restauración en entregas de turno.
- **[catalogos]** Poblado inicial de sectores, orígenes, tipos de procedimiento y dotación desde Planilla Central 2026.

## [v1.0.0] - 2026-10-20 — `v1.0.0`
**Lanzamiento Inicial BuinSeguro MiniCAD**

### ✨ Nuevas Funcionalidades (Features)
- **[core]** Lanzamiento inicial de la plataforma de despacho, recepción de llamadas, gestión de procedimientos y consola de terreno.
