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
        Schema::create('procedure_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procedure_id')->constrained('procedures')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users'); // Quién anota la novedad
            
            $table->string('action_type'); // Ej: "Despacho", "Llegada al lugar", "Anotación post-cierre"
            $table->text('description')->nullable();
            
            // Geolocalización opcional para cuando el inspector marca desde terreno
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procedure_logs');
    }
};
