<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Orden jerárquico: Roles -> Sectores -> Tipos de Procedimiento -> Catálogos Central 2026 -> Usuarios (Telefonistas e Inspectores)
        $this->call([
            RoleSeeder::class,
            SectorSeeder::class,
            ProcedureTypeSeeder::class,
            CentralAdministrationCatalogSeeder::class,
            UserSeeder::class,
        ]);
    }
}