<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Origin;
use App\Models\Device;
use App\Models\EventCategory;
use App\Models\SystemCatalogItem;
use Illuminate\Support\Str;

class CentralAdministrationCatalogSeeder extends Seeder
{
    /**
     * Pobla Orígenes de Ingreso, Dispositivos/Móviles, Resoluciones, Modalidades de Patrullaje,
     * Conjuntos Habitacionales y Categorías de Contingencia extraídos de:
     * PLANILLA REGISTRO CENTRAL 2026 - ADMINISTRACION.csv
     */
    public function run(): void
    {
        // 1. INGRESO -> Tabla origins (Orígenes de Llamado / Ingreso)
        $origins = [
            '1426',
            'CELULAR',
            'WHATSAPP',
            'COMUNICACIÓN INTERNA',
            'ORDEN JEFATURA',
            'SOLICITUD CARABINEROS',
            'SOLICITUD PDI',
            'SOLICITUD ALCALDICIA',
            'ANONIMO',
            'CENTRAL CAMARA',
            'PRESENCIAL',
            'RADIO',
            'ALARMA COMUNITARIA',
        ];

        foreach ($origins as $originName) {
            $normalized = trim($originName);
            $existing = Origin::whereRaw('LOWER(name) = ?', [mb_strtolower($normalized)])->first();
            if (!$existing) {
                Origin::create(['name' => $normalized]);
            }
        }

        // 2. DISPOSITIVO -> Tabla devices (Móviles, Motos, Infantería y Unidades)
        $devices = [
            'MOVIL 38',
            'MOVIL 1',
            'MOVIL 41',
            'MOVIL 40',
            'MOVIL 45',
            'MOVIL 50',
            'MOTO 046',
            'MOTO 047',
            'MOTO 048',
            'MOTO 049',
            'MOTO 050',
            'INFANTERIA',
            'BUIN SEGURO',
            'MOVIL 48',
            'MOVIL 53',
            'PATRULLA BETA',
            'MOTO 78',
            'MOTO 80',
            'MOTO 81',
            'MOTO 84',
            'MOTO 85',
            'MOTO 86',
            'NO PROCEDE',
        ];

        foreach ($devices as $deviceName) {
            $normalized = trim($deviceName);
            $existing = Device::whereRaw('LOWER(name) = ?', [mb_strtolower($normalized)])->first();
            if (!$existing) {
                Device::create([
                    'name' => $normalized,
                    'operational_status' => 'Disponible (QAP)',
                ]);
            }
        }

        // 3. PATRULLAJE -> SystemCatalogItem (patrol_modalities)
        $patrolModalities = [
            ['name' => 'PREVENTIVO', 'color' => 'emerald', 'icon' => 'fa-shield-halved'],
            ['name' => 'OS14- MIXTO', 'color' => 'blue', 'icon' => 'fa-people-Carry-box'],
            ['name' => 'NO PROCEDE', 'color' => 'slate', 'icon' => 'fa-ban'],
        ];

        foreach ($patrolModalities as $idx => $mod) {
            SystemCatalogItem::updateOrCreate(
                [
                    'catalog_group' => 'patrol_modalities',
                    'name' => $mod['name'],
                ],
                [
                    'code' => strtoupper(Str::slug($mod['name'], '_')),
                    'icon' => $mod['icon'],
                    'color' => $mod['color'],
                    'sort_order' => $idx + 1,
                    'is_active' => true,
                ]
            );
        }

        // 4. RESOLUCION -> SystemCatalogItem (procedure_resolutions)
        $resolutions = [
            'DERIVADO A CARABINEROS',
            'DERIVADO A PDI',
            'DERIVADO A DEPARTAMENTO MUNICIPAL',
            'DERIVADO A BUIN SEGURO',
            'DERIVADO A PATRULLA MIXTA',
            'DERIVADO A AMBULANCIAS',
            'DERIVADO A BOMBEROS',
            'DERIVADO A EMPRESA EXTERNA CGE/ AGUAS ANDINAS ETC',
            'DECOMISO',
            'DERIVADO A AUTOPISTA',
            'DERIVADO A MINISTERIO DE TRANSPORTE',
            'LESION MENOS GRAVES',
            'LESION GRAVE',
            'SIN LESIONES',
            'MUERTE',
            'DETENIDO BUIN SEGURO',
            'DETENIDO CARABINERO',
            'DERIVADO A ELECTRICO MUNICIPAL',
            'DETENIDO PDI',
            'DETENCION CIUDADANA',
            'DENUNCIA',
            'FALSA ALARMA',
            'SOLUCIONADO EN EL LUGAR',
            'INFRACCION TRANSITO',
            'INFRACCION MUNICIPAL',
            'LESION LEVE',
            'SIN NOVEDAD',
            'TRASLADO DE PERSONA A CENTRO ASISTENCIAL',
            'DERIVADO A OPERADOR DE CAMARAS',
            'LESIONES INCOMPATIBLES CON VIDA',
            'DERIVACION GRUA',
            'DERIVADO ALBERGUE',
            'DEPENDENCIAS MUNICIPALES',
            'DERIVADO A CGE',
            'DERIVADO A MODULO BUIN ORIENTE',
            'DERIVADO A MODULO EL RULO',
            'DERIVADO A MODULO VALDIVIA DE PAINE',
            'DERIVADO A OTRA JURISDICCION',
            'LABOR EDUCATIVA',
            'DERIVADO A PAINE MAS SEGURO',
            'NOTIFICADO PARA RETIRO',
            'RETIRO DE VEHICULO',
            'SACOS DE ARENA',
        ];

        foreach ($resolutions as $idx => $resName) {
            $cleanRes = trim($resName);
            $color = str_contains($cleanRes, 'DERIVADO') ? 'indigo' : (str_contains($cleanRes, 'DETENIDO') || str_contains($cleanRes, 'MUERTE') || str_contains($cleanRes, 'GRAVE') ? 'rose' : 'emerald');

            SystemCatalogItem::updateOrCreate(
                [
                    'catalog_group' => 'procedure_resolutions',
                    'name' => $cleanRes,
                ],
                [
                    'code' => strtoupper(Str::limit(Str::slug($cleanRes, '_'), 40, '')),
                    'icon' => 'fa-clipboard-check',
                    'color' => $color,
                    'sort_order' => $idx + 1,
                    'is_active' => true,
                ]
            );
        }

        // 5. CONJUNTOS HABITACIONALES -> SystemCatalogItem (housing_complexes)
        $housingComplexes = [
            'VALLES DEL MAIPO',
            'LOS GUINDOS / CAMINO LOS TILOS',
            'EL MOLINO',
            'VILLA ESPERANZA',
            'VILLA SANTA ISABEL',
            'MARIA ELENA SOTO',
            'LOS VIÑEDOS',
            'BUIN CENTRO',
            'TIERRAS DEL MAIPO',
            'VILLA EL PROGRESO',
            'MAIPO CENTRO',
            'BRISAS DEL MAIPO',
            'LA PALOMA',
            'EL NOGAL LOS ROBLES',
            'BARRIO PLAZA',
            'LINDEROS',
            'CABO CARLOS FIGUEROA',
            'POBLACION GABRIELA MISTRAL',
            'POBLACION LAS TURBINAS',
            'VILLASECA',
            'VILLA SANTA RITA',
            'PAULA JARAQUEMADA',
            'MIRAFLORES',
            'COMUNIDAD SAN MIGUEL',
            'SANTA ROSA',
            'VILLA VIRGINIA',
            'VILLA GABRIELA',
            'MANUEL PLAZA',
            'NUEVO BUIN',
            'VALDIVIA DE PAINE',
            'BAJOS DEL MAIPO',
            'DESPERTAR DEL MAIPO',
            'LOS MAGNOLIOS',
            'PEDRO WOLTERS',
            'VICENTE HUIDOBRO',
            'SAN LUIS',
            'CRUZ DEL SUR',
            'LAS MORERAS',
            'JOSE MIGUEL CARRERA',
            'EL DIAMANTE',
            'LOS JARDINES I',
            'LOS JARDINES II',
            'LAS DIEZ CUADRAS',
            'SANTA ADELA',
            'VILLA SANTA ADELA',
            'LAS COMPUERTAS',
            'LOS UMBRALES',
            'RUCALHUE',
            'VALLE BUIN I',
            'LOS JARDINES DE BUIN',
            'VALLE BUIN II',
            'LO SALINAS',
            'VILLA LAS PALMAS',
            'CONDOMINIO LO SALINAS',
            'LOS TULIPEROS',
            'LOS FORJADORES',
            'VILLA LO SALINAS',
            'POBLACION MANUEL RENGIFO',
            'REINA JULIANA',
            'VILLA AMSTERDAM',
            'WALDO ROMERO',
            'LA FUENTE',
            'CONDOMINIO ANIBAL PINTO',
            'POBLACION LAS REJAS',
            'LAS ACACIAS',
            'ARTURO PRAT',
            'EL DESPERTAR',
            'MANUEL RODRÍGUEZ',
            'RUBÉN LAMICH',
            'CAMPUSANO',
            'LAS PATAGUAS',
            'JORGE WASHINGTON',
            'PADRE HURTADO',
            'VILLA MAGISTERIO',
            'VILLA EL SOLAR',
            'VILLA SANTA MARIA',
            'VILLA MARIO ORTIZ',
            'EL OLIVAR (EL PEDREGAL)',
            'EL MADRIGAL',
            'BUIN ORIENTE',
            'ALBERTO KRUM ORIENTE',
            'AMAUTAS',
            'VILLA LOS HIDALGOS',
            'LOS CIRUELOS',
            'BARRIO NUEVO VILLASECA',
            'LOS COPAHUES',
            'LOS MOLINEROS',
            'CONDOMINIO JARDINES DEL SUR',
            'DOÑA JACINTA',
            'SANTA PAULINA',
            'DON PEDRO',
            'LOS PALTOS',
            'JARDIN VILLASECA',
            'LAS VIÑAS',
            'RAUL SILVA HENRIQUEZ',
            'HARAS DE BUIN',
            'TERRA NOBLE',
            'CAICURA',
            'VILLASECA ORIENTE',
            'COLO- COLO',
            'LOS ARENEROS',
            'CAMPAMENTO LOS PALTOS',
            'EL RECURSO',
            'CERVERA',
            'VILLORRIO SAN JOSE',
            'LAS CUMBRES',
            'QUINTA DE MAIPO',
            'VILLA ESTACION',
            'CASCO HISTORICO',
            'VILLA LAS FLORES',
            'LA ALBORADA',
            'SANTA FILOMENA',
            'CALLEJON LOS JELVES',
            'VILLA SANTA TERESA',
            'VILLA RIVERA SUR',
            'CAMPAMENTO MAIPO',
            'CAMPAMENTO CAMINO VILUCO',
            'LAS DELICIAS',
            'LAS VERTIENTES',
            'CAMPAMENTO LAGUNA MAIPO',
            'LA ESTANCILLA',
            'SALVADOR ALLENDE',
            'CALLEJON LA CRUZ- SIMON REYES',
            'LA CAPILLA',
            'MIGUEL DE CERVANTES',
            'VALLE ARAUCARIAS',
            'CLUB DE CAMPO ARAUCARIAS',
            'EL CERRILLO',
            'CONDOMINIO LAS PALMERAS',
            'CONDOMINIO TERRA BUENA',
            'HACIENDA LIMAY',
            'LOS CANTAROS',
            'ALTO SACRAMENTO',
            'SANTA TERESA DE LINDEROS',
            'PARCELAS LORETO',
            'HUERTOS LORETO',
            'LA FABRICA',
            'SAN ANDRES',
            'SANTA ELENA',
            'SANTA VICTORIA',
            'SANTA ROSA DE LINDEROS',
            'EL TREBOL',
            'LOS ROBLES II',
            'PARQUE BUIN',
            'AIRES DE BUIN',
            'HUERTOS DE BUIN',
            'ALTO BUIN',
            'PRADOS DE BUIN',
            'PLAZA BUIN III',
            'PLAZA BUIN II',
            'LAS CAMELIAS',
            'LA VILLA O LA ESPERANZA',
            'LAS ORQUIDEAS',
            'MORROS DE HASPARREN',
            'JARDIN LAS ORQUIDEAS',
            'PLAZA BUIN I',
            'SAN MIGUEL',
            'CONDOMINIO DOÑA JAVIERA',
            'EL PARQUE VILLASECA',
            'LOS NOGALES',
            'LA SIEMBRA',
            'RINCÓN LOS LIBERALES',
            'EL RECUERDO DE VILUCO',
            'HUERTOS DE BUIN I',
            'BAJOS DE MATTE',
            'NO IDENTIFICADO',
            'VILLA LOS MAÑIOS',
            'VILLA LOS PARRONALES',
            'VILLA VOLARE',
            'PUENTES DE BUIN',
            'CARRETERA',
            'CALETERA ORIENTE',
            'VILLA MAIPO 70',
            'CALETERA PONIENTE',
            'VILLA SANTA CLARA',
            'VILLA EL QUIJOTE',
            'VILLA LOS JARDINES DE LINDEROS',
            'VILLA PORTALES DE SANTA ADELA',
            'EL MONTE',
            'LAS CANOAS',
            'VILLA SAN JOSE',
            'RIO MAIPO',
            'LOS MORROS',
            'EL CAMPANARIO',
            'RUTA G-46',
            'TERRA VERDE',
            'VIÑAS DE BUIN',
            'ROGELIO UGARTE',
            'VILLA UMBRALES DE BUIN',
            'VILLA VICTORIA OSORIO',
            'MAIPO',
            'PARQUES DE BUIN SUR',
            'CAMINO BUIN MAIPO',
            'VALLE TRANQUILO',
            'LOS BUINENSES',
        ];

        foreach ($housingComplexes as $idx => $hcName) {
            $cleanHc = trim($hcName, " \t\n\r\0\x0B.,");
            SystemCatalogItem::updateOrCreate(
                [
                    'catalog_group' => 'housing_complexes',
                    'name' => $cleanHc,
                ],
                [
                    'code' => strtoupper(Str::limit(Str::slug($cleanHc, '_'), 40, '')),
                    'icon' => 'fa-house-chimney',
                    'color' => 'blue',
                    'sort_order' => $idx + 1,
                    'is_active' => true,
                ]
            );
        }

        // 6. Categorías de Eventos Masivos / Contingencias alineadas con la planilla
        $eventCategories = [
            ['name' => 'Inundación / Domicilio Anegado', 'icon' => '🌊', 'color' => 'blue'],
            ['name' => 'Caída de Árbol o Rama en Vía Pública', 'icon' => '🌳', 'color' => 'emerald'],
            ['name' => 'Corte / Fuego en Tendido Eléctrico', 'icon' => '⚡', 'color' => 'amber'],
            ['name' => 'Desborde de Río o Acequia', 'icon' => '🛟', 'color' => 'indigo'],
            ['name' => 'Solicitud Nylon o Sacos de Arena', 'icon' => '🏠', 'color' => 'orange'],
            ['name' => 'Poste Caído o en Mal Estado', 'icon' => '🚧', 'color' => 'rose'],
            ['name' => 'Rotura de Matriz / Aguas Servidas', 'icon' => '💧', 'color' => 'blue'],
            ['name' => 'Socavón / Calzada en Mal Estado', 'icon' => '🪨', 'color' => 'slate'],
        ];

        foreach ($eventCategories as $cat) {
            EventCategory::updateOrCreate(
                ['name' => $cat['name']],
                [
                    'icon' => $cat['icon'],
                    'color' => $cat['color'],
                    'is_active' => true,
                ]
            );
        }
    }
}
