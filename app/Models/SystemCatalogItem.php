<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class SystemCatalogItem extends Model
{
    protected $fillable = [
        'catalog_group',
        'name',
        'code',
        'icon',
        'color',
        'meta',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'meta' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public const GROUPS = [
        'operator_shift_blocks' => [
            'label' => 'Horarios Turnos Operadores (Sala)',
            'description' => 'Bloques horarios rotativos de sala (ej: Turno 1 08:00-14:00, Turno 2 14:00-20:00, Turno 3 20:00-08:00) y dotación mínima sugerida.',
            'has_shift_meta' => true,
        ],
        'operator_station_roles' => [
            'label' => 'Puestos / Funciones en Sala CAD',
            'description' => 'Roles operativos dentro del turno de central (ej: Operador Telefónico, Operador de Cámaras CCTV, Despachador Radial).',
            'has_shift_meta' => false,
        ],
        'patrol_shift_blocks' => [
            'label' => 'Horarios Turnos Patrullas (Flota)',
            'description' => 'Bloques horarios disponibles para la planificación de patrullas en terreno.',
            'has_shift_meta' => false,
        ],
        'patrol_crew_roles' => [
            'label' => 'Roles de Tripulación (Patrullas)',
            'description' => 'Funciones asignables a los inspectores dentro de un móvil (Conductor, Acompañante, Jefe de Patrulla).',
            'has_shift_meta' => false,
        ],
        'call_classifications' => [
            'label' => 'Tipificaciones Llamadas PBX',
            'description' => 'Motivos de clasificación para llamadas telefónicas que no generan procedimiento (Información, Pitanza, Derivación, etc.).',
            'has_shift_meta' => false,
        ],
        'procedure_log_actions' => [
            'label' => 'Acciones de Bitácora de Casos',
            'description' => 'Tipos de anotación disponibles al registrar avances en la bitácora de un procedimiento.',
            'has_shift_meta' => false,
        ],
        'device_statuses' => [
            'label' => 'Estados Operativos de Flota (QAP)',
            'description' => 'Estados seleccionables para los móviles en sala y en terreno (Disponible QAP, En Colación, Punto Fijo, En Mantención).',
            'has_shift_meta' => false,
        ],
        'event_types' => [
            'label' => 'Tipos de Contingencia / Eventos Masivos',
            'description' => 'Clasificación general al declarar una contingencia comunal (Frente de Mal Tiempo, Corte de Suministro, Operativo Especial).',
            'has_shift_meta' => false,
        ],
    ];

    /**
     * Obtiene los nombres activos de un grupo de catálogo con fallback seguro si la tabla aún no existe.
     */
    public static function namesFor(string $group, array $fallback = []): array
    {
        try {
            if (!Schema::hasTable('system_catalog_items')) {
                return $fallback;
            }

            $names = self::where('catalog_group', $group)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->pluck('name')
                ->toArray();

            return !empty($names) ? $names : $fallback;
        } catch (\Throwable $e) {
            return $fallback;
        }
    }

    /**
     * Obtiene la colección de registros activos de un grupo con fallback.
     */
    public static function itemsFor(string $group)
    {
        try {
            if (!Schema::hasTable('system_catalog_items')) {
                return collect();
            }

            return self::where('catalog_group', $group)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    public function getBadgeClassesAttribute(): string
    {
        return match ($this->color) {
            'green', 'emerald' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            'yellow', 'amber' => 'bg-amber-100 text-amber-800 border-amber-300',
            'orange' => 'bg-orange-100 text-orange-800 border-orange-300',
            'red', 'rose' => 'bg-rose-100 text-rose-800 border-rose-300',
            'purple', 'indigo' => 'bg-indigo-100 text-indigo-800 border-indigo-300',
            'gray', 'slate' => 'bg-slate-100 text-slate-800 border-slate-300',
            default => 'bg-blue-100 text-blue-800 border-blue-300',
        };
    }
}
