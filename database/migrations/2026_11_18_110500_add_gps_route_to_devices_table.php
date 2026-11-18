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
        if (! Schema::hasColumn('devices', 'gps_route')) {
            Schema::table('devices', function (Blueprint $table) {
                $table->json('gps_route')->nullable()->after('last_location_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('devices', 'gps_route')) {
            Schema::table('devices', function (Blueprint $table) {
                $table->dropColumn('gps_route');
            });
        }
    }
};
