<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabla de Categorías para Eventos Operacionales / Contingencias Masivas
        if (!Schema::hasTable('event_categories')) {
            Schema::create('event_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('icon', 20)->default('📌');
                $table->string('color', 30)->default('blue');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            // Poblar con las categorías operacionales base
            $now = now();
            DB::table('event_categories')->insert([
                ['name' => 'Inundación / Anegamiento', 'icon' => '🌊', 'color' => 'blue', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Árbol o Rama Caída', 'icon' => '🌳', 'color' => 'green', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Corte de Luz / Tendido Eléctrico', 'icon' => '⚡', 'color' => 'yellow', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Voladura de Techumbre / Vivienda', 'icon' => '🏠', 'color' => 'orange', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Ruta Cortada / Semáforo / Socavón', 'icon' => '🚧', 'color' => 'red', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Apoyo Adulto Mayor / Social', 'icon' => '🛟', 'color' => 'purple', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Otro Reporte de Contingencia', 'icon' => '📌', 'color' => 'gray', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ]);
        }

        // 2. Esquema de campos meta dinámicos en Tipos de Procedimiento (procedure_types)
        Schema::table('procedure_types', function (Blueprint $table) {
            if (!Schema::hasColumn('procedure_types', 'meta_fields')) {
                $table->json('meta_fields')->nullable()->after('name');
            }
        });

        // 3. Categorización de Inicio (al llamar) y Metadatos dinámicos en Procedimientos (procedures)
        Schema::table('procedures', function (Blueprint $table) {
            if (!Schema::hasColumn('procedures', 'initial_procedure_type_id')) {
                $table->foreignId('initial_procedure_type_id')
                    ->nullable()
                    ->after('procedure_type_id')
                    ->constrained('procedure_types')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('procedures', 'metadata')) {
                $table->json('metadata')->nullable()->after('address_reference');
            }
        });

        // Sincronizar initial_procedure_type_id en procedimientos históricos que aún no lo tengan
        DB::table('procedures')
            ->whereNull('initial_procedure_type_id')
            ->update(['initial_procedure_type_id' => DB::raw('procedure_type_id')]);
    }

    public function down(): void
    {
        Schema::table('procedures', function (Blueprint $table) {
            if (Schema::hasColumn('procedures', 'initial_procedure_type_id')) {
                $table->dropConstrainedForeignId('initial_procedure_type_id');
            }
            if (Schema::hasColumn('procedures', 'metadata')) {
                $table->dropColumn('metadata');
            }
        });

        Schema::table('procedure_types', function (Blueprint $table) {
            if (Schema::hasColumn('procedure_types', 'meta_fields')) {
                $table->dropColumn('meta_fields');
            }
        });

        Schema::dropIfExists('event_categories');
    }
};
