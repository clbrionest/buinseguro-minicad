<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tabla de Patrullajes Preventivos
        Schema::create('preventive_patrols', function (Blueprint $table) {
            $table->id();
            $table->string('folio')->unique(); // Ej: PAT-2026-0001
            $table->foreignId('device_id')->constrained('devices');
            $table->foreignId('sector_id')->constrained('sectors');
            $table->foreignId('user_id')->constrained('users'); // Quien registra / inicia
            $table->foreignId('patrol_assignment_id')->nullable()->constrained('patrol_assignments')->nullOnDelete();
            $table->string('patrol_type')->default('Ronda Preventiva General');
            $table->string('status')->default('En Curso'); // En Curso, Completado, Cancelado
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->integer('start_odometer')->nullable();
            $table->integer('end_odometer')->nullable();
            $table->text('mission_objective')->nullable();
            $table->text('completion_notes')->nullable();
            $table->json('gps_route')->nullable(); // [{lat, lng, recorded_at, speed}]
            $table->timestamps();
        });

        // 2. Tabla de Hitos / Puntos de Control del Patrullaje Preventivo
        Schema::create('preventive_patrol_checkpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('preventive_patrol_id')->constrained('preventive_patrols')->cascadeOnDelete();
            $table->foreignId('landmark_id')->nullable()->constrained('landmarks')->nullOnDelete();
            $table->string('name');
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->integer('order')->default(0);
            $table->string('status')->default('Pendiente'); // Pendiente, Validado, Omitido
            $table->dateTime('validated_at')->nullable();
            $table->foreignId('validated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('validation_lat', 10, 8)->nullable();
            $table->decimal('validation_lng', 11, 8)->nullable();
            $table->integer('validation_distance_meters')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 3. Vincular Procedimientos a Patrullajes Preventivos (Cuando un patrullaje da inicio a un caso)
        Schema::table('procedures', function (Blueprint $table) {
            $table->foreignId('preventive_patrol_id')->nullable()->after('patrol_assignment_id')
                ->constrained('preventive_patrols')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('procedures', function (Blueprint $table) {
            $table->dropForeign(['preventive_patrol_id']);
            $table->dropColumn('preventive_patrol_id');
        });

        Schema::dropIfExists('preventive_patrol_checkpoints');
        Schema::dropIfExists('preventive_patrols');
    }
};
