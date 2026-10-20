<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Sector;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Genera los usuarios Administradores, Telefonistas en Servicio (Rol Operador)
     * e Inspectores Municipales (Rol Inspector) extraídos de:
     * PLANILLA REGISTRO CENTRAL 2026 - ADMINISTRACION.csv
     *
     * Formato de correo: nombre.apellido@buinseguro.cl
     * Contraseña para Telefonistas e Inspectores: Buinseguro$
     */
    public function run(): void
    {
        $allSectorIds = Sector::pluck('id')->toArray();

        // 1. Administrador Principal del Sistema
        $admin = User::updateOrCreate(
            ['email' => 'admin@buinseguro.cl'],
            [
                'name' => 'Admin Sistema',
                'password' => Hash::make('Buinseguro$'),
            ]
        );
        $admin->syncRoles(['Administrador']);
        if (!empty($allSectorIds)) {
            $admin->sectors()->sync($allSectorIds);
        }

        // 2. Telefonistas en Servicio (Columna: TELEFONISTA EN SERVICIO -> Rol: Operador)
        $telefonistas = [
            ['name' => 'MARY DIAZ', 'extension' => '101'],
            ['name' => 'NAYARET JORQUERA', 'extension' => '102'],
            ['name' => 'BRISA ROA', 'extension' => '103'],
            ['name' => 'VALENTINA CONTRERAS', 'extension' => '104'],
            ['name' => 'IGNACIO GONZALEZ', 'extension' => '105'],
            ['name' => 'CATALINA PEREZ', 'extension' => '106'],
            ['name' => 'IGNACIO ZAMORA', 'extension' => '107'],
            ['name' => 'KARINA FIGUEROA', 'extension' => '108'],
            ['name' => 'CINDY HERNANDEZ', 'extension' => '109'],
        ];

        $defaultPasswordHash = Hash::make('Buinseguro$');

        foreach ($telefonistas as $tel) {
            $cleanName = trim($tel['name']);
            $email = self::buildEmailFromName($cleanName);

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $cleanName,
                    'password' => $defaultPasswordHash,
                    'extension' => $tel['extension'],
                ]
            );

            $user->syncRoles(['Operador']);
            if (!empty($allSectorIds)) {
                $user->sectors()->sync($allSectorIds);
            }
        }

        // 3. Inspectores Municipales (Columna: INSPECTOR MUNICIPAL -> Rol: Inspector)
        $inspectores = [
            'AMELIA FLORES',
            'ANDRÉS SÁNCHEZ',
            'ARIEL FUENTES',
            'CAMILA MADRID',
            'CRISTIAN PINO',
            'CRISTOFER URRUTIA',
            'FARES BAIS',
            'FRANCISCO PLAZA',
            'GERARDO SAM',
            'JOAQUÍN NAZAR',
            'JUAN ANDRADE',
            'LUIS GAETE',
            'LUIS MUÑOZ',
            'MANUEL LEYTON',
            'MARIO RAMÍREZ',
            'MIGUEL GUTIÉRREZ',
            'MIGUEL ROMERO',
            'MIGUEL VERGARA',
            'VICTORIA LOBOS',
            'JOSE RODRIGUEZ',
            'HENRY IGOR',
            'ANTONELLA VILCHES',
            'BENJAMIN TOLADO',
            'BORIS VENEGAS',
            'CRISTINA GUZMAN',
            'FRANCISCO HERMOSILLA',
            'GISSELLE MARTINEZ',
            'IGNACIO GARCIA',
            'JORDAN VEGA',
            'MARLON URBINA',
            'MARTIN ROMAN',
            'NICOLAS CACERES',
            'NICOLAS TOLEDO',
            'SEBASTIAN CHACON',
            'TOMAS VASQUEZ',
        ];

        foreach ($inspectores as $inspectorName) {
            $cleanName = trim($inspectorName);
            $email = self::buildEmailFromName($cleanName);

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $cleanName,
                    'password' => $defaultPasswordHash,
                ]
            );

            $user->syncRoles(['Inspector']);
            if (!empty($allSectorIds)) {
                $user->sectors()->sync($allSectorIds);
            }
        }
    }

    /**
     * Genera el correo corporativo a partir del nombre:
     * Ej: "AMELIA FLORES" -> "amelia.flores@buinseguro.cl"
     *     "ANDRÉS SÁNCHEZ" -> "andres.sanchez@buinseguro.cl"
     */
    public static function buildEmailFromName(string $fullName): string
    {
        $slug = Str::slug(Str::ascii(mb_strtolower(trim($fullName))), '.');
        return $slug . '@buinseguro.cl';
    }
}