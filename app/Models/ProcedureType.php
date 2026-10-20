<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcedureType extends Model
{
    protected $fillable = [
        'name',
        'meta_fields',
    ];

    protected $casts = [
        'meta_fields' => 'array',
    ];

    /**
     * Plantillas predefinidas de campos meta para facilitar la configuración en el Mantenedor
     */
    public static function getPresetTemplates(): array
    {
        return [
            'accidente_vehicular' => [
                'name' => '🚗 Accidente Vehicular / Tránsito',
                'fields' => [
                    ['key' => 'vehiculos_involucrados', 'label' => 'Cant. Vehículos Involucrados', 'type' => 'number', 'placeholder' => 'Ej: 2', 'options' => ''],
                    ['key' => 'ppu_vehiculos', 'label' => 'PPU / Patentes de Vehículos', 'type' => 'text', 'placeholder' => 'Ej: ABCD-12, JKLM-34', 'options' => ''],
                    ['key' => 'cantidad_heridos', 'label' => 'Cantidad de Lesionados / Heridos', 'type' => 'number', 'placeholder' => '0', 'options' => ''],
                    ['key' => 'cantidad_fallecidos', 'label' => 'Cantidad de Fallecidos', 'type' => 'number', 'placeholder' => '0', 'options' => ''],
                    ['key' => 'estado_via', 'label' => 'Estado de la Calzada / Vía', 'type' => 'select', 'placeholder' => '', 'options' => 'Tránsito Expedito,Pista Parcialmente Bloqueada,Ruta Totalmente Cortada'],
                    ['key' => 'apoyo_emergencia', 'label' => 'Apoyo Externo Requerido', 'type' => 'select', 'placeholder' => '', 'options' => 'No requiere,SAMU / Ambulancia,Bomberos,Carabineros,Múltiple (SAMU + Bomberos + Carabineros)'],
                ],
            ],
            'robo_delito' => [
                'name' => '🚨 Robo / Portonazo / Delito',
                'fields' => [
                    ['key' => 'especie_sustraida', 'label' => 'Especie o Bien Afectado', 'type' => 'text', 'placeholder' => 'Ej: Vehículo, especies domicilio, celular...', 'options' => ''],
                    ['key' => 'ppu_vehiculo', 'label' => 'PPU Vehículo Involucrado / Sustraído', 'type' => 'text', 'placeholder' => 'Ej: XXXX-99 (Marca/Color)', 'options' => ''],
                    ['key' => 'cantidad_sospechosos', 'label' => 'Cantidad de Sospechosos', 'type' => 'number', 'placeholder' => 'Ej: 3', 'options' => ''],
                    ['key' => 'uso_armas', 'label' => 'Presencia / Uso de Armas', 'type' => 'select', 'placeholder' => '', 'options' => 'No se observa / Se desconoce,Arma Blanca,Arma de Fuego,Objeto Contundente'],
                    ['key' => 'direccion_huida', 'label' => 'Dirección de Huida / Vestimenta', 'type' => 'text', 'placeholder' => 'Ej: Huyen hacia Ruta 5 Norte en sedán gris', 'options' => ''],
                ],
            ],
            'incendio_emergencia' => [
                'name' => '🔥 Incendio / Quema / Fuga',
                'fields' => [
                    ['key' => 'tipo_foco', 'label' => 'Tipo de Foco / Inmueble', 'type' => 'select', 'placeholder' => '', 'options' => 'Vivienda / Estructural,Pastizal / Forestal,Vehicular,Basural / Escombros,Fuga de Gas / Hazmat'],
                    ['key' => 'peligro_propagacion', 'label' => 'Peligro de Propagación', 'type' => 'select', 'placeholder' => '', 'options' => 'Alto (Viviendas colindantes),Medio,Bajo / Controlado'],
                    ['key' => 'cantidad_afectados', 'label' => 'Personas Damnificadas / Lesionadas', 'type' => 'number', 'placeholder' => '0', 'options' => ''],
                    ['key' => 'unidades_apoyo', 'label' => 'Unidades de Emergencia en Lugar', 'type' => 'text', 'placeholder' => 'Ej: Bomberos 1ª y 2ª Cía, CGE', 'options' => ''],
                ],
            ],
            'incivilidad_ruidos' => [
                'name' => '🔊 Ruidos Molestos / Fiscalización / VIF',
                'fields' => [
                    ['key' => 'origen_problema', 'label' => 'Origen / Fuente del Reporte', 'type' => 'text', 'placeholder' => 'Ej: Fiesta en domicilio, local comercial, vía pública', 'options' => ''],
                    ['key' => 'resolucion_terreno', 'label' => 'Acción / Resolución en Terreno', 'type' => 'select', 'placeholder' => '', 'options' => 'Advertencia Verbal / Diálogo,Citación a Juzgado Policía Local (JPL),Derivado a Carabineros,Sin moradores / Sin novedad al llegar'],
                    ['key' => 'folio_citacion', 'label' => 'N° Boleta / Parte / Citación', 'type' => 'text', 'placeholder' => 'Opcional', 'options' => ''],
                ],
            ],
        ];
    }

    /**
     * Devuelve los campos meta configurados en BD, o sugiere automáticamente una plantilla según el nombre si aún no se han configurado
     */
    public function getEffectiveMetaFieldsAttribute(): array
    {
        if (!empty($this->meta_fields) && is_array($this->meta_fields)) {
            return $this->meta_fields;
        }

        $lower = mb_strtolower($this->name ?? '');
        $presets = self::getPresetTemplates();

        if (str_contains($lower, 'accidente') || str_contains($lower, 'choque') || str_contains($lower, 'colisi') || str_contains($lower, 'atropello') || str_contains($lower, 'tránsito') || str_contains($lower, 'transito') || str_contains($lower, 'vehicular')) {
            return $presets['accidente_vehicular']['fields'];
        }

        if (str_contains($lower, 'robo') || str_contains($lower, 'hurto') || str_contains($lower, 'portonazo') || str_contains($lower, 'asalto') || str_contains($lower, 'delito') || str_contains($lower, 'sospechoso')) {
            return $presets['robo_delito']['fields'];
        }

        if (str_contains($lower, 'incendio') || str_contains($lower, 'fuego') || str_contains($lower, 'quema') || str_contains($lower, 'humo') || str_contains($lower, 'fuga')) {
            return $presets['incendio_emergencia']['fields'];
        }

        if (str_contains($lower, 'ruido') || str_contains($lower, 'ebrio') || str_contains($lower, 'comercio') || str_contains($lower, 'fiscalizaci') || str_contains($lower, 'violencia') || str_contains($lower, 'riña')) {
            return $presets['incivilidad_ruidos']['fields'];
        }

        return [];
    }
}
