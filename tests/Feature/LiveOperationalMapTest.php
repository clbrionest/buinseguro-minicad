<?php

namespace Tests\Feature;

use App\Livewire\LiveOperationalMap;
use App\Models\Device;
use App\Models\Origin;
use App\Models\Procedure;
use App\Models\ProcedureType;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LiveOperationalMapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Administrador', 'Supervisor', 'Operador', 'Inspector'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }
    }

    public function test_authenticated_user_can_view_operational_map_without_redundant_title(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Operador');

        $response = $this->actingAs($user)->get(route('map.live'));

        $response->assertStatus(200);
        $response->assertDontSee('📡 MAPA OPERATIVO EN VIVO');
        $response->assertSee('cad-map-container');
        $response->assertSee('operational-map');
        $response->assertSee('Táctico');
        $response->assertSee('Urbano');
        $response->assertSee('Satélite');
    }

    public function test_operational_map_loads_active_procedures_and_devices(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $origin = Origin::create(['name' => 'Llamada 1426']);
        $sector = Sector::create(['name' => 'Sector 1']);
        $type = ProcedureType::create(['name' => '🚗 Accidente Vehicular']);

        $procedure = Procedure::create([
            'user_id' => $admin->id,
            'origin_id' => $origin->id,
            'sector_id' => $sector->id,
            'procedure_type_id' => $type->id,
            'exact_address' => 'Av. San Martín 123',
            'status' => 'Pendiente',
            'latitude' => -33.7325,
            'longitude' => -70.7415,
        ]);

        $device = Device::create([
            'name' => 'M-01',
            'imei' => '123456789012345',
            'status' => 'Disponible',
            'last_lat' => -33.7330,
            'last_lng' => -70.7420,
            'last_location_at' => now(),
        ]);

        Livewire::actingAs($admin)
            ->test(LiveOperationalMap::class)
            ->assertSet('counts.pending', 1)
            ->assertSet('counts.vehicles', 1)
            ->assertDispatched('map-updated');
    }

    public function test_sector_visibility_restriction_for_non_admin_users(): void
    {
        $sectorA = Sector::create(['name' => 'Sector Norte']);
        $sectorB = Sector::create(['name' => 'Sector Sur']);

        $user = User::factory()->create();
        $user->assignRole('Inspector');
        $user->sectors()->attach($sectorA->id);

        $origin = Origin::create(['name' => 'Denuncia']);
        $type = ProcedureType::create(['name' => '🚨 Seguridad']);

        // Procedimiento en sector permitido (Sector A)
        Procedure::create([
            'user_id' => $user->id,
            'origin_id' => $origin->id,
            'sector_id' => $sectorA->id,
            'procedure_type_id' => $type->id,
            'exact_address' => 'Calle Norte 100',
            'status' => 'En Camino',
            'latitude' => -33.7310,
            'longitude' => -70.7400,
        ]);

        // Procedimiento en sector NO permitido (Sector B)
        Procedure::create([
            'user_id' => $user->id,
            'origin_id' => $origin->id,
            'sector_id' => $sectorB->id,
            'procedure_type_id' => $type->id,
            'exact_address' => 'Calle Sur 200',
            'status' => 'En Camino',
            'latitude' => -33.7390,
            'longitude' => -70.7490,
        ]);

        Livewire::actingAs($user)
            ->test(LiveOperationalMap::class)
            ->assertSet('counts.on_way', 1);
    }

    public function test_operational_map_includes_vehicle_gps_route_and_layer_controls(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $device = Device::create([
            'name' => 'M-05',
            'operational_status' => 'Disponible (QAP)',
        ]);

        $device->recordGpsPoint(-33.7320, -70.7420, 35);
        $device->recordGpsPoint(-33.7335, -70.7440, 40);

        $component = Livewire::actingAs($admin)
            ->test(LiveOperationalMap::class)
            ->assertSee('layer-btn-procedures')
            ->assertSee('layer-btn-vehicles')
            ->assertSee('layer-btn-routes');

        $vehicles = $component->get('vehicles');
        $this->assertCount(1, $vehicles);
        $this->assertSame('M-05', $vehicles[0]['name']);
        $this->assertGreaterThanOrEqual(2, count($vehicles[0]['route']));
    }
}
