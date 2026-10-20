<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar la caché de Spatie antes de crear roles
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear los roles usando el modelo de Spatie
        Role::create(['name' => 'Administrador']);
        Role::create(['name' => 'Supervisor']);
        Role::create(['name' => 'Operador']);
        Role::create(['name' => 'Inspector']);
    }
}