
# Buin Seguro MiniCAD — Reglas de desarrollo

## Stack
- Laravel 13, PHP 8.4 en Docker.
- MySQL 8.4, Livewire 3, Volt.
- Tailwind CSS y Alpine.js.
- Spatie Laravel Permission.

## Arquitectura
- Respetar las convenciones existentes.
- Utilizar Form Requests cuando corresponda.
- Aplicar Policies y permisos en el servidor.
- Evitar lógica de negocio compleja en las vistas.
- Mantener la trazabilidad de procedimientos y llamadas.

## Seguridad
- Nunca trabajar con datos reales de producción.
- Nunca mostrar, modificar ni publicar credenciales.
- No ejecutar migraciones destructivas sin aprobación.
- No eliminar ni modificar registros históricos sin autorización.
- No cambiar roles o autorizaciones sin revisar su impacto.
- No ejecutar despliegues en servidores productivos.

## Entorno
- Ejecutar Composer y Artisan mediante Docker Compose.
- Utilizar MySQL local del proyecto.
- No ejecutar Composer con PHP 8.5 de WSL.
- Mantener cambios en ramas feature o chore.
- No modificar main directamente.

## Calidad
- Incorporar pruebas cuando se modifica funcionalidad.
- Ejecutar Laravel Pint.
- Validar permisos de cada rol afectado.
- Informar errores y pruebas no ejecutadas.
- Mantener cambios pequeños y verificables.
