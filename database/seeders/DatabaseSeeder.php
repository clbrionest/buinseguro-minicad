<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // El orden es importante: primero los roles, luego los usuarios
        $this->call([
            RoleSeeder::class,
            SectorSeeder::class,
            // ProcedureTypeSeeder::class, // (Descomenta cuando lo llenes)
            UserSeeder::class,
        ]);
    }
}