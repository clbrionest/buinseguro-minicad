<?php

namespace Tests\Feature;

use App\Livewire\CreatePreventivePatrol;
use App\Livewire\CreateProcedure;
use App\Livewire\FieldInspectorPanel;
use App\Livewire\ManagePreventivePatrols;
use App\Livewire\ShowPreventivePatrol;
use App\Models\Device;
use App\Models\Landmark;
use App\Models\Origin;
use App\Models\PreventivePatrol;
use App\Models\PreventivePatrolCheckpoint;
use App\Models\Procedure;
use App\Models\ProcedureType;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PreventivePatrolTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Device $device;

    protected Sector $sector;

    protected Landmark $landmark;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Administrador', 'Supervisor', 'Operador', 'Inspector'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');

        $this->sector = Sector::create(['name' => 'Sector 1 - Centro']);
        $this->device = Device::create([
            'name' => 'M-01',
            'imei' => '123456789012345',
            'operational_status' => 'Disponible',
            'last_lat' => -33.7320,
            'last_lng' => -70.7420,
            'last_location_at' => now(),
        ]);

        $this->landmark = Landmark::create([
            'name' => 'Liceo Haydée Azócar',
            'address' => 'Balmaceda 450',
            'latitude' => -33.7330,
            'longitude' => -70.7410,
        ]);
    }

    public function test_can_create_preventive_patrol_with_checkpoints(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreatePreventivePatrol::class)
            ->call('openModal')
            ->set('device_id', $this->device->id)
            ->set('sector_id', $this->sector->id)
            ->set('patrol_type', 'Ronda Escolar / Colegios')
            ->set('selected_landmarks', [$this->landmark->id])
            ->set('start_odometer', 150000)
            ->set('mission_objective', 'Monitoreo de salida de alumnos')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('preventive-patrol-created');

        $this->assertDatabaseHas('preventive_patrols', [
            'device_id' => $this->device->id,
            'sector_id' => $this->sector->id,
            'patrol_type' => 'Ronda Escolar / Colegios',
            'status' => 'En Curso',
            'start_odometer' => 150000,
        ]);

        $patrol = PreventivePatrol::first();
        $this->assertNotNull($patrol);
        $this->assertStringStartsWith('PAT-', $patrol->folio);
        $this->assertEquals(1, $patrol->checkpoints()->count());
        $this->assertEquals('Liceo Haydée Azócar', $patrol->checkpoints->first()->name);
        $this->assertEquals('Pendiente', $patrol->checkpoints->first()->status);
    }

    public function test_can_validate_and_omit_checkpoint_in_show_view(): void
    {
        $patrol = PreventivePatrol::create([
            'folio' => 'PAT-2026-0001',
            'device_id' => $this->device->id,
            'sector_id' => $this->sector->id,
            'user_id' => $this->admin->id,
            'patrol_type' => 'Ronda Preventiva General',
            'status' => 'En Curso',
            'started_at' => now(),
        ]);

        $checkpoint1 = PreventivePatrolCheckpoint::create([
            'preventive_patrol_id' => $patrol->id,
            'landmark_id' => $this->landmark->id,
            'name' => $this->landmark->name,
            'address' => $this->landmark->address,
            'latitude' => $this->landmark->latitude,
            'longitude' => $this->landmark->longitude,
            'order' => 1,
            'status' => 'Pendiente',
        ]);

        $checkpoint2 = PreventivePatrolCheckpoint::create([
            'preventive_patrol_id' => $patrol->id,
            'landmark_id' => null,
            'name' => 'Plaza de Armas',
            'address' => 'Plaza Buin',
            'latitude' => -33.7320,
            'longitude' => -70.7420,
            'order' => 2,
            'status' => 'Pendiente',
        ]);

        // Validar checkpoint 1
        Livewire::actingAs($this->admin)
            ->test(ShowPreventivePatrol::class, ['patrol' => $patrol])
            ->call('validateCheckpoint', $checkpoint1->id, -33.7331, -70.7411, 'Móvil en el punto')
            ->assertHasNoErrors();

        $checkpoint1->refresh();
        $this->assertEquals('Validado', $checkpoint1->status);
        $this->assertNotNull($checkpoint1->validated_at);
        $this->assertEquals($this->admin->id, $checkpoint1->validated_by_user_id);
        $this->assertNotNull($checkpoint1->validation_distance_meters);

        // Omitir checkpoint 2
        Livewire::actingAs($this->admin)
            ->test(ShowPreventivePatrol::class, ['patrol' => $patrol])
            ->call('omitCheckpoint', $checkpoint2->id, 'Ruta bloqueada por obras')
            ->assertHasNoErrors();

        $checkpoint2->refresh();
        $this->assertEquals('Omitido', $checkpoint2->status);
    }

    public function test_can_complete_preventive_patrol(): void
    {
        $patrol = PreventivePatrol::create([
            'folio' => 'PAT-2026-0002',
            'device_id' => $this->device->id,
            'sector_id' => $this->sector->id,
            'user_id' => $this->admin->id,
            'patrol_type' => 'Ronda Preventiva General',
            'status' => 'En Curso',
            'started_at' => now()->subHours(2),
            'start_odometer' => 100000,
        ]);

        Livewire::actingAs($this->admin)
            ->test(ShowPreventivePatrol::class, ['patrol' => $patrol])
            ->set('end_odometer', 100025)
            ->set('completion_notes', 'Ronda finalizada sin novedades graves.')
            ->call('completePatrol')
            ->assertHasNoErrors();

        $patrol->refresh();
        $this->assertEquals('Completado', $patrol->status);
        $this->assertNotNull($patrol->ended_at);
        $this->assertEquals(100025, $patrol->end_odometer);
    }

    public function test_can_start_procedure_linked_to_preventive_patrol(): void
    {
        $patrol = PreventivePatrol::create([
            'folio' => 'PAT-2026-0003',
            'device_id' => $this->device->id,
            'sector_id' => $this->sector->id,
            'user_id' => $this->admin->id,
            'patrol_type' => 'Ronda Preventiva General',
            'status' => 'En Curso',
            'started_at' => now(),
        ]);

        $origin = Origin::create(['name' => 'Patrullaje Preventivo']);
        $type = ProcedureType::create(['name' => '🚨 Robo / Portonazo']);

        Livewire::actingAs($this->admin)
            ->test(CreateProcedure::class)
            ->call('openModalWithPatrol', $patrol->id)
            ->assertSet('preventive_patrol_id', $patrol->id)
            ->assertSet('preventivePatrolFolio', $patrol->folio)
            ->assertSet('device_id', $this->device->id)
            ->assertSet('sector_id', $this->sector->id)
            ->set('procedure_type_id', $type->id)
            ->set('exact_address', 'Av. San Martín 800')
            ->set('initial_description', 'Delito detectado en flagrancia durante patrullaje preventivo.')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('procedure-created');

        $procedure = Procedure::where('exact_address', 'Av. San Martín 800')->first();
        $this->assertNotNull($procedure);
        $this->assertEquals($patrol->id, $procedure->preventive_patrol_id);

        $patrol->refresh();
        $this->assertEquals(1, $patrol->procedures()->count());
        $this->assertEquals($procedure->id, $patrol->procedures->first()->id);
    }

    public function test_preventive_patrol_print_report_route(): void
    {
        $patrol = PreventivePatrol::create([
            'folio' => 'PAT-2026-0004',
            'device_id' => $this->device->id,
            'sector_id' => $this->sector->id,
            'user_id' => $this->admin->id,
            'patrol_type' => 'Ronda Preventiva General',
            'status' => 'Completado',
            'started_at' => now()->subHour(),
            'ended_at' => now(),
            'start_odometer' => 100000,
            'end_odometer' => 100015,
        ]);

        $response = $this->actingAs($this->admin)->get(route('patrols.preventive.print', $patrol));

        $response->assertStatus(200);
        $response->assertSee('PAT-2026-0004');
        $response->assertSee('Informe Operativo de Patrullaje Preventivo');
        $response->assertSee('M-01');
    }

    public function test_inspector_can_add_additional_patrol_checkpoint_from_field_panel(): void
    {
        $patrol = PreventivePatrol::create([
            'folio' => 'PAT-2026-0005',
            'device_id' => $this->device->id,
            'sector_id' => $this->sector->id,
            'user_id' => $this->admin->id,
            'patrol_type' => 'Ronda Preventiva General',
            'status' => 'En Curso',
            'started_at' => now(),
        ]);

        Livewire::actingAs($this->admin)
            ->test(FieldInspectorPanel::class)
            ->set('selected_device_id', $this->device->id)
            ->call('toggleAddPatrolPointForm')
            ->assertSet('showAddPatrolPointForm', true)
            ->set('new_patrol_point_name', 'Plaza Villa Estación')
            ->set('new_patrol_point_address', 'Av. Estación con Maipo')
            ->set('validate_new_point_immediately', true)
            ->call('addAdditionalPatrolCheckpoint', -33.7345, -70.7435)
            ->assertHasNoErrors()
            ->assertSet('showAddPatrolPointForm', false)
            ->assertSee('Plaza Villa Estación');

        $this->assertEquals(1, $patrol->checkpoints()->count());
        $addedCheckpoint = $patrol->checkpoints()->first();
        $this->assertEquals('Plaza Villa Estación', $addedCheckpoint->name);
        $this->assertEquals('Validado', $addedCheckpoint->status);
    }

    public function test_manage_preventive_patrols_refreshes_automatically_on_patrol_created_event(): void
    {
        $component = Livewire::actingAs($this->admin)
            ->test(ManagePreventivePatrols::class)
            ->assertSee('No se encontraron patrullajes preventivos');

        $patrol = PreventivePatrol::create([
            'folio' => 'PAT-2026-0099',
            'device_id' => $this->device->id,
            'sector_id' => $this->sector->id,
            'user_id' => $this->admin->id,
            'patrol_type' => 'Ronda Nocturna Residencial',
            'status' => 'En Curso',
            'started_at' => now(),
        ]);

        $component->dispatch('preventive-patrol-created', patrolId: $patrol->id)
            ->assertSee('PAT-2026-0099')
            ->assertSee('Patrullaje Preventivo PAT-2026-0099 iniciado exitosamente.');
    }
}
