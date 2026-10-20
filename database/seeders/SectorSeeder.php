<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SectorSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('sectors')->insert([
            ['name' => 'Buin Oriente', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Linderos', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Maipo', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Valdivia de Paine', 'created_at' => now(), 'updated_at' => now()],
            // Puedes agregar más sectores aquí
        ]);
    }
}