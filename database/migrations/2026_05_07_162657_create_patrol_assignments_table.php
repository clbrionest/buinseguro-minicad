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
        // 1. La cabecera: El Móvil y su misión general en el turno
        Schema::create('patrol_assignments', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('shift_name'); // Ej: 1er Turno (07:00 a 16:00)
            $table->foreignId('device_id')->nullable()->constrained('devices'); // Móvil
            $table->foreignId('sector_id')->nullable()->constrained('sectors'); // Sector principal
            $table->text('mission_notes')->nullable(); // Ej: "07:10 Liceo 131..."
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. La tabla pivote: Quién se sube, con qué rol, y sus excepciones de horario
        Schema::create('patrol_assignment_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patrol_assignment_id')->constrained('patrol_assignments')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users');
            $table->string('role'); // Conductor, Supervisor, Paramédico, Acompañante
            $table->string('time_exception')->nullable(); // Ej: "Apoya hasta las 05:00"
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patrol_assignments');
    }
};
