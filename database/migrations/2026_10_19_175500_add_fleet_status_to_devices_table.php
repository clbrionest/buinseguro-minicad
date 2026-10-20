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
        Schema::table('devices', function (Blueprint $table) {
            $table->string('operational_status')->default('Disponible (QAP)')->after('name');
            $table->string('status_notes')->nullable()->after('operational_status');
            $table->decimal('last_lat', 10, 8)->nullable()->after('status_notes');
            $table->decimal('last_lng', 11, 8)->nullable()->after('last_lat');
            $table->timestamp('last_location_at')->nullable()->after('last_lng');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn([
                'operational_status',
                'status_notes',
                'last_lat',
                'last_lng',
                'last_location_at',
            ]);
        });
    }
};
