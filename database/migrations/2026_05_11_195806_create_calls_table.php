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
        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->string('asterisk_id')->unique(); // El UNIQUEID de Asterisk
            $table->string('caller_id');            // Número que llama
            $table->string('extension');            // Extensión que recibe
            $table->string('status')->default('ringing'); // ringing, answered, completed
            
            // Relación opcional con tu tabla de requerimientos/emergencias
            $table->foreignId('procedure_id')->nullable()->constrained('procedures');
            
            $table->string('recording_path')->nullable(); // Ruta del audio
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};
