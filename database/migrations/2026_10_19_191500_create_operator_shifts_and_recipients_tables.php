<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('operator_shifts')) {
            Schema::create('operator_shifts', function (Blueprint $table) {
                $table->id();
                $table->string('shift_type', 80)->default('Mañana (08:00 - 16:00)');
                $table->foreignId('opened_by_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('closed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('received_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->json('operators_on_duty')->nullable();
                $table->json('equipment_status')->nullable();
                $table->text('opening_notes')->nullable();
                $table->text('shift_log_notes')->nullable();
                $table->text('handover_notes')->nullable();
                $table->json('stats_snapshot')->nullable();
                $table->timestamp('started_at');
                $table->timestamp('closed_at')->nullable();
                $table->timestamp('handover_acknowledged_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('email_sent')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('shift_report_recipients')) {
            Schema::create('shift_report_recipients', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email');
                $table->string('role_title', 100)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            $now = now();
            DB::table('shift_report_recipients')->insert([
                [
                    'name' => 'Director de Seguridad Pública',
                    'email' => 'director.seguridad@buin.cl',
                    'role_title' => 'Dirección de Seguridad Pública',
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => 'Coordinación Operativa Central CAD',
                    'email' => 'coordinacion.seguridad@buin.cl',
                    'role_title' => 'Jefatura de Operaciones',
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_report_recipients');
        Schema::dropIfExists('operator_shifts');
    }
};
