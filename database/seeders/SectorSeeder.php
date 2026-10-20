<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sector;

class SectorSeeder extends Seeder
{
    /**
     * Sectores jurisdiccionales extraídos de PLANILLA REGISTRO CENTRAL 2026 - ADMINISTRACION.csv
     */
    public function run(): void
    {
        $sectors = [
            'CASCO HISTÓRICO',
            'BUIN NORTE',
            'BUIN SUR',
            'BUIN ORIENTE',
            'BUIN PONIENTE',
            'NUEVO BUIN',
            'MAIPO',
            'ALTO JAHUEL',
            'LINDEROS',
            'VILUCO',
            'VALDIVIA DE PAINE',
            'LA ESTANCILLA',
            'EL RECURSO',
            'RURAL NORTE',
            'LOS GUINDOS',
            'FLOR DE MAIPO',
            'EL CERRILLO',
            'CERVERA',
            'NUEVO LINDEROS',
            'VILLASECA',
            'RUTA 5 SUR',
            'ACCESO SUR',
            'RIBERA SUR',
            'SIN INFORMACION',
        ];

        foreach ($sectors as $sectorName) {
            $normalized = trim($sectorName);
            $existing = Sector::whereRaw('LOWER(name) = ?', [mb_strtolower($normalized)])->first();

            if ($existing) {
                $existing->update(['name' => $normalized]);
            } else {
                Sector::create(['name' => $normalized]);
            }
        }
    }
}