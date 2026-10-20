<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabla unificada para todos los catálogos, etiquetas e ítems dinámicos del sistema
        if (!Schema::hasTable('system_catalog_items')) {
            Schema::create('system_catalog_items', function (Blueprint $table) {
                $table->id();
                $table->string('catalog_group', 60)->index();
                $table->string('name', 150);
                $table->string('code', 60)->nullable();
                $table->string('icon', 60)->nullable();
                $table->string('color', 30)->default('blue');
                $table->json('meta')->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['catalog_group', 'name']);
            });

            $now = now();
            $seedItems = [
                // Bloques Horarios de Turnos de Operadores (Rotativos: 08-14, 14-20, 20-08)
                [
                    'catalog_group' => 'operator_shift_blocks',
                    'name' => 'Turno 1 (08:00 a 14:00)',
                    'code' => 'T1_08_14',
                    'icon' => 'fa-sun',
                    'color' => 'blue',
                    'meta' => json_encode(['start_time' => '08:00', 'end_time' => '14:00', 'min_phone_ops' => 2, 'min_camera_ops' => 4]),
                    'sort_order' => 1,
                ],
                [
                    'catalog_group' => 'operator_shift_blocks',
                    'name' => 'Turno 2 (14:00 a 20:00)',
                    'code' => 'T2_14_20',
                    'icon' => 'fa-cloud-sun',
                    'color' => 'amber',
                    'meta' => json_encode(['start_time' => '14:00', 'end_time' => '20:00', 'min_phone_ops' => 2, 'min_camera_ops' => 4]),
                    'sort_order' => 2,
                ],
                [
                    'catalog_group' => 'operator_shift_blocks',
                    'name' => 'Turno 3 (20:00 a 08:00)',
                    'code' => 'T3_20_08',
                    'icon' => 'fa-moon',
                    'color' => 'indigo',
                    'meta' => json_encode(['start_time' => '20:00', 'end_time' => '08:00', 'min_phone_ops' => 2, 'min_camera_ops' => 5]),
                    'sort_order' => 3,
                ],

                // Funciones / Puestos de Operadores en Sala CAD
                [
                    'catalog_group' => 'operator_station_roles',
                    'name' => 'Operador Telefónico',
                    'code' => 'PHONE_OP',
                    'icon' => 'fa-headset',
                    'color' => 'blue',
                    'meta' => null,
                    'sort_order' => 1,
                ],
                [
                    'catalog_group' => 'operator_station_roles',
                    'name' => 'Operador de Cámaras (CCTV)',
                    'code' => 'CAMERA_OP',
                    'icon' => 'fa-video',
                    'color' => 'indigo',
                    'meta' => null,
                    'sort_order' => 2,
                ],
                [
                    'catalog_group' => 'operator_station_roles',
                    'name' => 'Despachador Radial',
                    'code' => 'RADIO_OP',
                    'icon' => 'fa-walkie-talkie',
                    'color' => 'emerald',
                    'meta' => null,
                    'sort_order' => 3,
                ],
                [
                    'catalog_group' => 'operator_station_roles',
                    'name' => 'Jefe de Sala / Supervisor',
                    'code' => 'SUPERVISOR_OP',
                    'icon' => 'fa-user-shield',
                    'color' => 'amber',
                    'meta' => null,
                    'sort_order' => 4,
                ],

                // Bloques Horarios de Turnos de Patrullas
                [
                    'catalog_group' => 'patrol_shift_blocks',
                    'name' => 'Primer Turno (07:00 a 16:00)',
                    'code' => 'PATROL_T1',
                    'icon' => 'fa-sun',
                    'color' => 'blue',
                    'meta' => json_encode(['start_time' => '07:00', 'end_time' => '16:00']),
                    'sort_order' => 1,
                ],
                [
                    'catalog_group' => 'patrol_shift_blocks',
                    'name' => 'Segundo Turno (15:00 a 00:00)',
                    'code' => 'PATROL_T2',
                    'icon' => 'fa-cloud-sun',
                    'color' => 'amber',
                    'meta' => json_encode(['start_time' => '15:00', 'end_time' => '00:00']),
                    'sort_order' => 2,
                ],
                [
                    'catalog_group' => 'patrol_shift_blocks',
                    'name' => 'Tercer Turno (00:00 a 08:00)',
                    'code' => 'PATROL_T3',
                    'icon' => 'fa-moon',
                    'color' => 'indigo',
                    'meta' => json_encode(['start_time' => '00:00', 'end_time' => '08:00']),
                    'sort_order' => 3,
                ],

                // Roles de Tripulación en Patrulla
                [
                    'catalog_group' => 'patrol_crew_roles',
                    'name' => 'Conductor',
                    'code' => 'DRIVER',
                    'icon' => 'fa-steering-wheel',
                    'color' => 'blue',
                    'meta' => null,
                    'sort_order' => 1,
                ],
                [
                    'catalog_group' => 'patrol_crew_roles',
                    'name' => 'Acompañante',
                    'code' => 'COMPANION',
                    'icon' => 'fa-user-check',
                    'color' => 'emerald',
                    'meta' => null,
                    'sort_order' => 2,
                ],
                [
                    'catalog_group' => 'patrol_crew_roles',
                    'name' => 'Jefe de Patrulla',
                    'code' => 'PATROL_LEAD',
                    'icon' => 'fa-user-tie',
                    'color' => 'amber',
                    'meta' => null,
                    'sort_order' => 3,
                ],

                // Tipificaciones de Llamadas PBX sin Procedimiento
                [
                    'catalog_group' => 'call_classifications',
                    'name' => 'Información General',
                    'code' => 'INFO',
                    'icon' => 'fa-circle-info',
                    'color' => 'blue',
                    'meta' => null,
                    'sort_order' => 1,
                ],
                [
                    'catalog_group' => 'call_classifications',
                    'name' => 'Pitanza / Broma',
                    'code' => 'PRANK',
                    'icon' => 'fa-triangle-exclamation',
                    'color' => 'red',
                    'meta' => null,
                    'sort_order' => 2,
                ],
                [
                    'catalog_group' => 'call_classifications',
                    'name' => 'Llamada Cortada',
                    'code' => 'DROPPED',
                    'icon' => 'fa-phone-slash',
                    'color' => 'gray',
                    'meta' => null,
                    'sort_order' => 3,
                ],
                [
                    'catalog_group' => 'call_classifications',
                    'name' => 'Número Equivocado',
                    'code' => 'WRONG_NUMBER',
                    'icon' => 'fa-ban',
                    'color' => 'yellow',
                    'meta' => null,
                    'sort_order' => 4,
                ],
                [
                    'catalog_group' => 'call_classifications',
                    'name' => 'Derivado a otra Institución',
                    'code' => 'REFERRED',
                    'icon' => 'fa-share-from-square',
                    'color' => 'purple',
                    'meta' => null,
                    'sort_order' => 5,
                ],

                // Tipos de Anotación en Bitácora de Procedimientos
                [
                    'catalog_group' => 'procedure_log_actions',
                    'name' => 'Novedad',
                    'code' => 'UPDATE',
                    'icon' => 'fa-comment-dots',
                    'color' => 'blue',
                    'meta' => null,
                    'sort_order' => 1,
                ],
                [
                    'catalog_group' => 'procedure_log_actions',
                    'name' => 'Llegada al lugar',
                    'code' => 'ARRIVAL',
                    'icon' => 'fa-location-dot',
                    'color' => 'green',
                    'meta' => null,
                    'sort_order' => 2,
                ],
                [
                    'catalog_group' => 'procedure_log_actions',
                    'name' => 'Contacto con Recurrente',
                    'code' => 'CALLER_CONTACT',
                    'icon' => 'fa-handshake',
                    'color' => 'indigo',
                    'meta' => null,
                    'sort_order' => 3,
                ],
                [
                    'catalog_group' => 'procedure_log_actions',
                    'name' => 'Coordinación Externa (Carabineros / SAMU / Bomberos)',
                    'code' => 'EXTERNAL_COORD',
                    'icon' => 'fa-truck-medical',
                    'color' => 'yellow',
                    'meta' => null,
                    'sort_order' => 4,
                ],
                [
                    'catalog_group' => 'procedure_log_actions',
                    'name' => 'Cierre de Procedimiento',
                    'code' => 'CLOSURE',
                    'icon' => 'fa-circle-check',
                    'color' => 'red',
                    'meta' => null,
                    'sort_order' => 5,
                ],

                // Estados Operativos de Móviles / Flota QAP
                [
                    'catalog_group' => 'device_statuses',
                    'name' => 'Disponible (QAP)',
                    'code' => 'QAP',
                    'icon' => 'fa-circle-check',
                    'color' => 'green',
                    'meta' => null,
                    'sort_order' => 1,
                ],
                [
                    'catalog_group' => 'device_statuses',
                    'name' => 'En Colación',
                    'code' => 'LUNCH',
                    'icon' => 'fa-utensils',
                    'color' => 'yellow',
                    'meta' => null,
                    'sort_order' => 2,
                ],
                [
                    'catalog_group' => 'device_statuses',
                    'name' => 'Punto Fijo',
                    'code' => 'FIXED_POINT',
                    'icon' => 'fa-map-pin',
                    'color' => 'blue',
                    'meta' => null,
                    'sort_order' => 3,
                ],
                [
                    'catalog_group' => 'device_statuses',
                    'name' => 'En Mantención',
                    'code' => 'MAINTENANCE',
                    'icon' => 'fa-wrench',
                    'color' => 'red',
                    'meta' => null,
                    'sort_order' => 4,
                ],

                // Tipos de Eventos Masivos / Contingencias
                [
                    'catalog_group' => 'event_types',
                    'name' => 'Frente de Mal Tiempo',
                    'code' => 'WEATHER',
                    'icon' => 'fa-cloud-showers-heavy',
                    'color' => 'blue',
                    'meta' => null,
                    'sort_order' => 1,
                ],
                [
                    'catalog_group' => 'event_types',
                    'name' => 'Corte Masivo de Suministro',
                    'code' => 'BLACKOUT',
                    'icon' => 'fa-bolt',
                    'color' => 'yellow',
                    'meta' => null,
                    'sort_order' => 2,
                ],
                [
                    'catalog_group' => 'event_types',
                    'name' => 'Incendio / Emergencia Mayor',
                    'code' => 'FIRE',
                    'icon' => 'fa-fire',
                    'color' => 'red',
                    'meta' => null,
                    'sort_order' => 3,
                ],
                [
                    'catalog_group' => 'event_types',
                    'name' => 'Evento Comunal Masivo',
                    'code' => 'PUBLIC_EVENT',
                    'icon' => 'fa-users',
                    'color' => 'purple',
                    'meta' => null,
                    'sort_order' => 4,
                ],
                [
                    'catalog_group' => 'event_types',
                    'name' => 'Operativo Especial',
                    'code' => 'SPECIAL_OPS',
                    'icon' => 'fa-shield-halved',
                    'color' => 'green',
                    'meta' => null,
                    'sort_order' => 5,
                ],
            ];

            foreach ($seedItems as &$item) {
                $item['is_active'] = true;
                $item['created_at'] = $now;
                $item['updated_at'] = $now;
            }

            DB::table('system_catalog_items')->insert($seedItems);
        }

        // 2. Agregar fecha de planificación de turno a operator_shifts si no existe
        if (Schema::hasTable('operator_shifts') && !Schema::hasColumn('operator_shifts', 'shift_date')) {
            Schema::table('operator_shifts', function (Blueprint $table) {
                $table->date('shift_date')->nullable()->after('id')->index();
            });
        }

        // 3. Tabla pivote de dotación asignada al turno de sala (igual que patrol_assignment_user)
        if (!Schema::hasTable('operator_shift_user')) {
            Schema::create('operator_shift_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('operator_shift_id')->constrained('operator_shifts')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('station_role', 80)->default('Operador Telefónico'); // Ej: Operador Telefónico, Operador de Cámaras (CCTV)
                $table->string('console_station', 80)->nullable(); // Ej: Anexo 101, Muro CCTV 1
                $table->string('time_exception', 120)->nullable(); // Ej: 08:00 a 12:00
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('operator_shift_user');
        if (Schema::hasTable('operator_shifts') && Schema::hasColumn('operator_shifts', 'shift_date')) {
            Schema::table('operator_shifts', function (Blueprint $table) {
                $table->dropColumn('shift_date');
            });
        }
        Schema::dropIfExists('system_catalog_items');
    }
};
