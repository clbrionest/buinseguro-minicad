<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Creamos al usuario usando Eloquent
        $admin = User::create([
            'name' => 'Admin Sistema',
            'email' => 'admin@buinseguro.cl',
            'password' => Hash::make('password123'),
        ]);

        // 2. Le asignamos el rol con la magia de Spatie
        $admin->assignRole('Administrador');
    }
}