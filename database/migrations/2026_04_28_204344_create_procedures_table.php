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
        Schema::create('procedures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users'); // Quién registró el caso
            $table->foreignId('origin_id')->constrained('origins'); // Ej: 1426, Comunicación Interna
            $table->foreignId('sector_id')->constrained('sectors');
            $table->foreignId('procedure_type_id')->constrained('procedure_types');
            $table->foreignId('device_id')->nullable()->constrained('devices'); // Puede estar vacío al inicio
            
            $table->string('exact_address');
            $table->string('address_reference')->nullable();
            
            // Datos del informante (pueden ser nulos en caso de patrullaje preventivo)
            $table->string('caller_name')->nullable();
            $table->string('caller_id_document')->nullable();
            $table->string('caller_phone')->nullable();
            
            // La máquina de estados
            $table->enum('status', ['Pendiente', 'En Camino', 'En el Lugar', 'Derivado', 'Cerrado'])->default('Pendiente');
            
            $table->timestamps(); // Crea automáticamente created_at (Hora de Inicio) y updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procedures');
    }
};
