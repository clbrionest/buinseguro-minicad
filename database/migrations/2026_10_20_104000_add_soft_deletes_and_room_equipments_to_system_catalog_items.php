<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('system_catalog_items') && !Schema::hasColumn('system_catalog_items', 'deleted_at')) {
            Schema::table('system_catalog_items', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (Schema::hasTable('system_catalog_items')) {
            $now = now();
            $defaultEquipments = [
                [
                    'catalog_group' => 'room_equipments',
                    'name' => 'Central PBX',
                    'code' => 'central_pbx',
                    'icon' => 'fa-phone-volume',
                    'color' => 'blue',
                    'sort_order' => 1,
                ],
                [
                    'catalog_group' => 'room_equipments',
                    'name' => 'Radio VHF',
                    'code' => 'radio_vhf',
                    'icon' => 'fa-walkie-talkie',
                    'color' => 'emerald',
                    'sort_order' => 2,
                ],
                [
                    'catalog_group' => 'room_equipments',
                    'name' => 'Cámaras CCTV',
                    'code' => 'camaras_cctv',
                    'icon' => 'fa-video',
                    'color' => 'indigo',
                    'sort_order' => 3,
                ],
                [
                    'catalog_group' => 'room_equipments',
                    'name' => 'GPS Flota',
                    'code' => 'gps_flota',
                    'icon' => 'fa-satellite-dish',
                    'color' => 'amber',
                    'sort_order' => 4,
                ],
                [
                    'catalog_group' => 'room_equipments',
                    'name' => 'Enlace Internet / Red CAD',
                    'code' => 'enlace_red',
                    'icon' => 'fa-network-wired',
                    'color' => 'blue',
                    'sort_order' => 5,
                ],
                [
                    'catalog_group' => 'room_equipments',
                    'name' => 'Respaldo UPS / Generador',
                    'code' => 'respaldo_ups',
                    'icon' => 'fa-car-battery',
                    'color' => 'emerald',
                    'sort_order' => 6,
                ],
            ];

            foreach ($defaultEquipments as $eq) {
                $exists = DB::table('system_catalog_items')
                    ->where('catalog_group', 'room_equipments')
                    ->where('name', $eq['name'])
                    ->exists();

                if (!$exists) {
                    DB::table('system_catalog_items')->insert(array_merge($eq, [
                        'meta' => null,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]));
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('system_catalog_items')) {
            DB::table('system_catalog_items')->where('catalog_group', 'room_equipments')->delete();

            if (Schema::hasColumn('system_catalog_items', 'deleted_at')) {
                Schema::table('system_catalog_items', function (Blueprint $table) {
                    $table->dropSoftDeletes();
                });
            }
        }
    }
};
