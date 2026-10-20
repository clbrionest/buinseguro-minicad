<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Models\Sector;
use App\Models\Origin;
use App\Models\ProcedureType;
use App\Models\Procedure;
use App\Models\Call;
use App\Models\OperatorShift;
use App\Models\ShiftReportRecipient;
use App\Models\AuditLog;
use App\Mail\ShiftClosedReportMail;
use Livewire\Livewire;
use App\Livewire\CreateProcedure;
use App\Livewire\ManageOperatorShifts;

class CadSecurityAndAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Administrador', 'Supervisor', 'Operador', 'Inspector'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }
    }

    public function test_rbac_blocks_unauthorized_roles_from_admin_and_shift_routes(): void
    {
        $inspector = User::factory()->create();
        $inspector->assignRole('Inspector');

        $operador = User::factory()->create(['extension' => '101']);
        $operador->assignRole('Operador');

        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        // Inspector no puede entrar a Usuarios, Mantenedores, Turnos de Sala ni Auditoría
        $this->actingAs($inspector)->get('/usuarios')->assertStatus(403);
        $this->actingAs($inspector)->get('/mantenedores')->assertStatus(403);
        $this->actingAs($inspector)->get('/turno-sala')->assertStatus(403);
        $this->actingAs($inspector)->get('/auditoria')->assertStatus(403);

        // Operador sí puede entrar a Turno de Sala, pero NO a Usuarios, Mantenedores ni Auditoría
        $this->actingAs($operador)->get('/turno-sala')->assertStatus(200);
        $this->actingAs($operador)->get('/usuarios')->assertStatus(403);
        $this->actingAs($operador)->get('/mantenedores')->assertStatus(403);
        $this->actingAs($operador)->get('/auditoria')->assertStatus(403);

        // Administrador tiene acceso completo
        $this->actingAs($admin)->get('/usuarios')->assertStatus(200);
        $this->actingAs($admin)->get('/mantenedores')->assertStatus(200);
        $this->actingAs($admin)->get('/auditoria')->assertStatus(200);
    }

    public function test_call_audio_controller_enforces_extension_ownership_and_logs_audit(): void
    {
        Storage::fake('asterisk_recordings');
        Storage::disk('asterisk_recordings')->put('2026/10/19/call-102.wav', 'fake-audio-content');

        $call102 = Call::create([
            'asterisk_id' => 'ast-999102',
            'caller_id' => '+56911112222',
            'extension' => '102',
            'status' => 'answered',
            'recording_path' => '2026/10/19/call-102.wav',
        ]);

        $operator101 = User::factory()->create(['extension' => '101']);
        $operator101->assignRole('Operador');

        $operator102 = User::factory()->create(['extension' => '102']);
        $operator102->assignRole('Operador');

        $supervisor = User::factory()->create(['extension' => '200']);
        $supervisor->assignRole('Supervisor');

        // 1. Operador del anexo 101 intenta escuchar llamada del anexo 102 -> 403 y registra security.denied
        $this->actingAs($operator101)
            ->get(route('calls.play', $call102))
            ->assertStatus(403);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $operator101->id,
            'action' => 'security.denied',
            'module' => 'Telefonía PBX',
        ]);

        // 2. Operador del anexo 102 escucha su propia llamada -> 200 OK y registra call.audio_played
        $this->actingAs($operator102)
            ->get(route('calls.play', $call102))
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $operator102->id,
            'action' => 'call.audio_played',
        ]);

        // 3. Supervisor puede escuchar llamadas de cualquier anexo -> 200 OK
        $this->actingAs($supervisor)
            ->get(route('calls.play', $call102))
            ->assertStatus(200);
    }

    public function test_call_audio_controller_blocks_path_traversal(): void
    {
        Storage::fake('asterisk_recordings');

        $maliciousCall = Call::create([
            'asterisk_id' => 'ast-traversal',
            'caller_id' => '12345',
            'extension' => '101',
            'status' => 'answered',
            'recording_path' => '../../.env',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $this->actingAs($admin)
            ->get(route('calls.play', $maliciousCall))
            ->assertStatus(404);
    }

    public function test_asterisk_webhook_validates_token_and_sanitizes_recording_path(): void
    {
        config(['services.asterisk.webhook_token' => 'secret-pbx-token-2026']);

        // Sin token -> 401
        $this->postJson('/v1/asterisk/webhook', [
            'id' => 'pbx-001',
            'numero' => '987654321',
            'extension' => '101',
            'status' => 'answered',
        ])->assertStatus(401);

        // Con token válido pero con intento de path traversal en recording_path
        $this->withHeaders(['X-Asterisk-Token' => 'secret-pbx-token-2026'])
            ->postJson('/v1/asterisk/webhook', [
                'id' => 'pbx-001',
                'numero' => '987654321',
                'extension' => '101',
                'status' => 'answered',
                'recording_path' => '../../../etc/passwd',
            ])->assertStatus(200);

        $call = Call::where('asterisk_id', 'pbx-001')->first();
        $this->assertNotNull($call);
        $this->assertNull($call->recording_path);
    }

    public function test_operator_cannot_create_or_view_procedure_in_unauthorized_sector(): void
    {
        $sectorNorte = Sector::create(['name' => 'Sector Norte']);
        $sectorSur = Sector::create(['name' => 'Sector Sur']);
        $origin = Origin::create(['name' => 'Central Telefónica']);
        $type = ProcedureType::create(['name' => 'Accidente Vehicular']);

        $operatorNorte = User::factory()->create();
        $operatorNorte->assignRole('Operador');
        $operatorNorte->sectors()->sync([$sectorNorte->id]);

        // Caso creado en Sector Sur
        $caseSur = Procedure::create([
            'user_id' => $operatorNorte->id,
            'origin_id' => $origin->id,
            'sector_id' => $sectorSur->id,
            'procedure_type_id' => $type->id,
            'initial_procedure_type_id' => $type->id,
            'exact_address' => 'Av. Sur 500',
            'status' => 'Pendiente',
        ]);

        // 1. Operador de Sector Norte no puede ver ficha ni detalle de Sector Sur
        $this->actingAs($operatorNorte)
            ->get(route('procedures.show', $caseSur))
            ->assertStatus(403);

        // 2. Operador de Sector Norte intenta forzar creación en Sector Sur vía Livewire -> 403
        Livewire::actingAs($operatorNorte)
            ->test(CreateProcedure::class)
            ->set('origin_id', $origin->id)
            ->set('sector_id', $sectorSur->id)
            ->set('procedure_type_id', $type->id)
            ->set('exact_address', 'Calle Prohibida 123')
            ->call('save')
            ->assertForbidden();

        // 3. Operador crea procedimiento en su Sector Norte autorizado con metadatos -> 200 OK y audita
        Livewire::actingAs($operatorNorte)
            ->test(CreateProcedure::class)
            ->set('origin_id', $origin->id)
            ->set('sector_id', $sectorNorte->id)
            ->set('procedure_type_id', $type->id)
            ->set('exact_address', 'Av. O\'Higgins 100, Buin')
            ->set('metaValues', ['ppu_vehiculos' => 'ABCD-12', 'vehiculos_involucrados' => '2'])
            ->call('save')
            ->assertHasNoErrors();

        $created = Procedure::where('exact_address', 'Av. O\'Higgins 100, Buin')->first();
        $this->assertNotNull($created);
        $this->assertSame('ABCD-12', $created->metadata['ppu_vehiculos'] ?? null);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $operatorNorte->id,
            'action' => 'procedure.created',
            'auditable_id' => $created->id,
        ]);
    }

    public function test_operator_shift_lifecycle_and_director_email_report_on_closure(): void
    {
        Mail::fake();

        $outgoingOp = User::factory()->create(['name' => 'Operador Saliente']);
        $outgoingOp->assignRole('Operador');

        $incomingOp = User::factory()->create(['name' => 'Operador Entrante']);
        $incomingOp->assignRole('Operador');

        ShiftReportRecipient::query()->delete();
        ShiftReportRecipient::create([
            'name' => 'Director Seguridad',
            'email' => 'director.seguridad@buin.cl',
            'role_title' => 'Director DSP',
            'is_active' => true,
        ]);

        // 1. Abrir Turno de Sala
        Livewire::actingAs($outgoingOp)
            ->test(ManageOperatorShifts::class)
            ->set('shift_type', 'Mañana (08:00 - 16:00)')
            ->set('opening_notes', 'Sala recibida conforme.')
            ->call('openNewShift')
            ->assertHasNoErrors();

        $shift = OperatorShift::where('is_active', true)->first();
        $this->assertNotNull($shift);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'shift.opened',
            'auditable_id' => $shift->id,
        ]);

        // 2. Cerrar Turno y Traspasar al Operador Entrante -> dispara email a Director
        Livewire::actingAs($outgoingOp)
            ->test(ManageOperatorShifts::class)
            ->set('shift_log_notes', 'Guardia sin caídas de enlace.')
            ->set('handover_notes', 'Dar seguimiento a patrullaje preventivo en Maipo.')
            ->set('received_by_user_id', $incomingOp->id)
            ->call('closeAndHandoverShift')
            ->assertHasNoErrors();

        $shift->refresh();
        $this->assertFalse($shift->is_active);
        $this->assertTrue($shift->email_sent);
        $this->assertSame($incomingOp->id, $shift->received_by_user_id);

        Mail::assertSent(ShiftClosedReportMail::class, function ($mail) {
            return $mail->hasTo('director.seguridad@buin.cl');
        });

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'shift.closed',
            'auditable_id' => $shift->id,
        ]);
    }

    public function test_catalogs_crud_and_validations(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        // 1. Crear Sector válido y rechazar duplicado
        Livewire::actingAs($admin)
            ->test(\App\Livewire\ManageCatalogs::class)
            ->set('activeTab', 'sectors')
            ->set('name', 'Sector Alto Jahuel')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('sectors', ['name' => 'Sector Alto Jahuel']);

        Livewire::actingAs($admin)
            ->test(\App\Livewire\ManageCatalogs::class)
            ->set('activeTab', 'sectors')
            ->set('name', 'Sector Alto Jahuel')
            ->call('save')
            ->assertHasErrors(['name']);

        // 2. Crear ítem de catálogo parametrizable (Rol de Estación de Sala) y validar unicidad en su grupo
        Livewire::actingAs($admin)
            ->test(\App\Livewire\ManageCatalogs::class)
            ->set('activeTab', 'operator_station_roles')
            ->set('name', 'Operador Dron / UAV')
            ->set('code', 'OP_UAV')
            ->set('color', 'indigo')
            ->set('sort_order', 5)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('system_catalog_items', [
            'catalog_group' => 'operator_station_roles',
            'name' => 'Operador Dron / UAV',
            'code' => 'OP_UAV',
        ]);

        Livewire::actingAs($admin)
            ->test(\App\Livewire\ManageCatalogs::class)
            ->set('activeTab', 'operator_station_roles')
            ->set('name', 'Operador Dron / UAV')
            ->call('save')
            ->assertHasErrors(['name']);
    }

    public function test_rotating_operator_shift_roster_with_phone_and_camera_quotas(): void
    {
        $supervisor = User::factory()->create(['name' => 'Supervisor Sala']);
        $supervisor->assignRole('Supervisor');

        $phoneOp1 = User::factory()->create(['name' => 'Tel 1', 'extension' => '101']);
        $phoneOp1->assignRole('Operador');
        $phoneOp2 = User::factory()->create(['name' => 'Tel 2', 'extension' => '102']);
        $phoneOp2->assignRole('Operador');

        $camOp1 = User::factory()->create(['name' => 'Cam 1']);
        $camOp1->assignRole('Operador');
        $camOp2 = User::factory()->create(['name' => 'Cam 2']);
        $camOp2->assignRole('Operador');
        $camOp3 = User::factory()->create(['name' => 'Cam 3']);
        $camOp3->assignRole('Operador');
        $camOp4 = User::factory()->create(['name' => 'Cam 4']);
        $camOp4->assignRole('Operador');

        // 1. Validar que no se permita repetir al mismo operador en dos estaciones del mismo turno
        Livewire::actingAs($supervisor)
            ->test(ManageOperatorShifts::class)
            ->set('filterDate', '2026-10-19')
            ->set('shift_type', 'Turno 1 (08:00 a 14:00)')
            ->set('crewMembers', [
                ['user_id' => $phoneOp1->id, 'station_role' => 'Operador Telefónico', 'console_station' => 'Anexo 101', 'time_exception' => ''],
                ['user_id' => $phoneOp1->id, 'station_role' => 'Operador de Cámaras (CCTV)', 'console_station' => 'Muro CCTV 1', 'time_exception' => ''],
            ])
            ->call('saveShiftRoster', false)
            ->assertHasErrors(['crewMembers']);

        // 2. Armar y activar Turno 1 (08:00 a 14:00) con 2 Operadores Telefónicos y 4 Operadores de Cámaras
        Livewire::actingAs($supervisor)
            ->test(ManageOperatorShifts::class)
            ->set('filterDate', '2026-10-19')
            ->set('shift_type', 'Turno 1 (08:00 a 14:00)')
            ->set('opening_notes', 'Dotación rotativa completa Turno 1.')
            ->set('crewMembers', [
                ['user_id' => $phoneOp1->id, 'station_role' => 'Operador Telefónico', 'console_station' => 'Anexo 101', 'time_exception' => ''],
                ['user_id' => $phoneOp2->id, 'station_role' => 'Operador Telefónico', 'console_station' => 'Anexo 102', 'time_exception' => ''],
                ['user_id' => $camOp1->id, 'station_role' => 'Operador de Cámaras (CCTV)', 'console_station' => 'CCTV 1 (Centro)', 'time_exception' => ''],
                ['user_id' => $camOp2->id, 'station_role' => 'Operador de Cámaras (CCTV)', 'console_station' => 'CCTV 2 (Maipo)', 'time_exception' => ''],
                ['user_id' => $camOp3->id, 'station_role' => 'Operador de Cámaras (CCTV)', 'console_station' => 'CCTV 3 (Linderos)', 'time_exception' => ''],
                ['user_id' => $camOp4->id, 'station_role' => 'Operador de Cámaras (CCTV)', 'console_station' => 'CCTV 4 (Viluco)', 'time_exception' => ''],
            ])
            ->call('saveShiftRoster', true)
            ->assertHasNoErrors();

        $shift = OperatorShift::where('shift_type', 'Turno 1 (08:00 a 14:00)')->with('crew')->first();
        $this->assertNotNull($shift);
        $this->assertTrue($shift->is_active);
        $this->assertCount(6, $shift->crew);
        $this->assertSame(2, $shift->crew->where('pivot.station_role', 'Operador Telefónico')->count());
        $this->assertSame(4, $shift->crew->where('pivot.station_role', 'Operador de Cámaras (CCTV)')->count());
    }
}

