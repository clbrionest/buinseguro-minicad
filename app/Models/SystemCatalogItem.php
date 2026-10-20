<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SystemCatalogItem extends Model
{
    use SoftDeletes;

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
        'room_equipments' => [
            'label' => 'Equipos en Sala (Apertura/Cierre)',
            'description' => 'Equipos y sistemas críticos de Sala CAD verificados en la apertura y cierre de turno (permite agregar o dar de baja lógica con Soft-Delete).',
            'has_shift_meta' => false,
        ],
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
        'procedure_resolutions' => [
            'label' => 'Resoluciones y Derivaciones (Cierre)',
            'description' => 'Resultados y derivaciones oficiales al resolver o derivar un procedimiento (Derivado a Carabineros, PDI, Solucionado en el Lugar, Infracción, etc.).',
            'has_shift_meta' => false,
        ],
        'patrol_modalities' => [
            'label' => 'Modalidades de Patrullaje',
            'description' => 'Tipos de patrullaje operativo (Preventivo, OS14- Mixto, No Procede).',
            'has_shift_meta' => false,
        ],
        'housing_complexes' => [
            'label' => 'Conjuntos Habitacionales / Villas',
            'description' => 'Catastro oficial de villas, poblaciones, condominios y conjuntos habitacionales de la comuna de Buin.',
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

    /**
     * Devuelve el mapa [code_key => Nombre Equipo] de los equipos en sala activos (no eliminados por soft-delete).
     */
    public static function roomEquipmentsMap(): array
    {
        $fallback = [
            'central_pbx' => 'Central PBX',
            'radio_vhf' => 'Radio VHF',
            'camaras_cctv' => 'Cámaras CCTV',
            'gps_flota' => 'GPS Flota',
        ];

        try {
            if (!Schema::hasTable('system_catalog_items')) {
                return $fallback;
            }

            $items = self::where('catalog_group', 'room_equipments')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            if ($items->isEmpty()) {
                return $fallback;
            }

            $map = [];
            foreach ($items as $item) {
                $key = !empty($item->code) ? strtolower($item->code) : Str::slug($item->name, '_');
                $map[$key] = $item->name;
            }

            return $map;
        } catch (\Throwable $e) {
            return $fallback;
        }
    }

    /**
     * Resuelve la etiqueta legible de un equipo en sala (incluyendo equipos dados de baja con Soft-Delete para actas históricas).
     */
    public static function resolveEquipmentLabel(string $key): string
    {
        $defaults = [
            'central_pbx' => 'Central PBX',
            'radio_vhf' => 'Radio VHF',
            'camaras_cctv' => 'Cámaras CCTV',
            'gps_flota' => 'GPS Flota',
            'enlace_red' => 'Enlace Internet / Red CAD',
            'respaldo_ups' => 'Respaldo UPS / Generador',
        ];

        try {
            if (Schema::hasTable('system_catalog_items')) {
                $found = self::withTrashed()
                    ->where('catalog_group', 'room_equipments')
                    ->where(function ($q) use ($key) {
                        $q->whereRaw('LOWER(code) = ?', [strtolower($key)])
                          ->orWhereRaw('LOWER(name) = ?', [strtolower(str_replace('_', ' ', $key))]);
                    })
                    ->first();

                if ($found) {
                    return $found->name;
                }
            }
        } catch (\Throwable $e) {
            // Fallback
        }

        return $defaults[$key] ?? ucwords(str_replace('_', ' ', $key));
    }
}
