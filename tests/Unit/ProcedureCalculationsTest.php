<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Procedure;
use App\Models\ProcedureType;
use Carbon\Carbon;

class ProcedureCalculationsTest extends TestCase
{
    public function test_haversine_distance_returns_zero_for_identical_coordinates(): void
    {
        $distance = Procedure::calculateDistanceMeters(-33.7325, -70.7428, -33.7325, -70.7428);

        $this->assertSame(0, $distance);
    }

    public function test_haversine_distance_returns_null_when_coordinates_are_missing(): void
    {
        $this->assertNull(Procedure::calculateDistanceMeters(null, -70.7428, -33.7325, -70.7428));
        $this->assertNull(Procedure::calculateDistanceMeters(-33.7325, -70.7428, null, null));
    }

    public function test_haversine_distance_calculates_accurate_meters_in_buin(): void
    {
        // Plaza de Buin (-33.7325, -70.7428) a ~500m al norte (-33.7280, -70.7428)
        $distance = Procedure::calculateDistanceMeters(-33.7325, -70.7428, -33.7280, -70.7428);

        $this->assertNotNull($distance);
        $this->assertGreaterThan(480, $distance);
        $this->assertLessThan(520, $distance);
    }

    public function test_sla_status_is_optimal_when_response_is_10_minutes_or_less(): void
    {
        $created = Carbon::parse('2026-10-19 10:00:00');
        $dispatched = Carbon::parse('2026-10-19 10:02:00');
        $arrived = Carbon::parse('2026-10-19 10:09:00'); // 7 min desde despacho

        $procedure = new Procedure([
            'status' => 'En el Lugar',
            'dispatched_at' => $dispatched,
            'arrived_at' => $arrived,
        ]);
        $procedure->created_at = $created;

        $this->assertSame(2, $procedure->dispatch_minutes);
        $this->assertSame(7, $procedure->arrival_minutes);
        $this->assertSame('green', $procedure->sla_traffic_light['color']);
    }

    public function test_sla_traffic_light_is_yellow_between_13_and_20_minutes(): void
    {
        $created = Carbon::parse('2026-10-19 10:00:00');
        $dispatched = Carbon::parse('2026-10-19 10:01:00');
        $arrived = Carbon::parse('2026-10-19 10:16:00'); // 15 min desde despacho

        $procedure = new Procedure([
            'status' => 'En el Lugar',
            'dispatched_at' => $dispatched,
            'arrived_at' => $arrived,
        ]);
        $procedure->created_at = $created;

        $this->assertSame(15, $procedure->arrival_minutes);
        $this->assertSame('yellow', $procedure->sla_traffic_light['color']);
    }

    public function test_sla_traffic_light_is_red_when_response_exceeds_20_minutes(): void
    {
        $created = Carbon::parse('2026-10-19 10:00:00');
        $dispatched = Carbon::parse('2026-10-19 10:05:00');
        $arrived = Carbon::parse('2026-10-19 10:30:00'); // 25 min desde despacho

        $procedure = new Procedure([
            'status' => 'Cerrado',
            'dispatched_at' => $dispatched,
            'arrived_at' => $arrived,
        ]);
        $procedure->created_at = $created;

        $this->assertSame(25, $procedure->arrival_minutes);
        $this->assertSame('red', $procedure->sla_traffic_light['color']);
    }

    public function test_procedure_type_effective_meta_fields_resolves_accident_presets(): void
    {
        $type = new ProcedureType([
            'name' => 'Accidente de Tránsito / Colisión Vehicular',
            'meta_fields' => null,
        ]);

        $fields = $type->effective_meta_fields;
        $keys = array_column($fields, 'key');

        $this->assertContains('vehiculos_involucrados', $keys);
        $this->assertContains('ppu_vehiculos', $keys);
        $this->assertContains('cantidad_heridos', $keys);
    }
}
