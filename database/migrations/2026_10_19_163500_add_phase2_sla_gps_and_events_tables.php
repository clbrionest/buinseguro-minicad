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
        Schema::table('procedures', function (Blueprint $table) {
            $table->timestamp('dispatched_at')->nullable()->after('status');
            $table->timestamp('mobile_arrived_at')->nullable()->after('dispatched_at');
            $table->decimal('mobile_arrival_lat', 10, 8)->nullable()->after('mobile_arrived_at');
            $table->decimal('mobile_arrival_lng', 11, 8)->nullable()->after('mobile_arrival_lat');
            $table->integer('mobile_arrival_distance')->nullable()->after('mobile_arrival_lng');
            $table->timestamp('arrived_at')->nullable()->after('mobile_arrival_distance');
            $table->timestamp('closed_at')->nullable()->after('arrived_at');
        });

        Schema::create('operational_events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('event_type')->default('Frente de Mal Tiempo');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('user_id')->constrained('users');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('event_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operational_event_id')->constrained('operational_events')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users');
            $table->string('category');
            $table->string('urgency')->default('Media'); // Alta, Media, Baja
            $table->foreignId('sector_id')->nullable()->constrained('sectors')->nullOnDelete();
            $table->string('exact_address');
            $table->string('address_reference')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('caller_name')->nullable();
            $table->string('caller_phone')->nullable();
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->string('status')->default('Pendiente'); // Pendiente, En Atención, Resuelto
            $table->text('notes')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_reports');
        Schema::dropIfExists('operational_events');

        Schema::table('procedures', function (Blueprint $table) {
            $table->dropColumn([
                'dispatched_at',
                'mobile_arrived_at',
                'mobile_arrival_lat',
                'mobile_arrival_lng',
                'mobile_arrival_distance',
                'arrived_at',
                'closed_at',
            ]);
        });
    }
};
