<?php

namespace Tests\Feature;

use App\Livewire\CallsReport;
use App\Livewire\FieldInspectorPanel;
use App\Livewire\ShowProcedure;
use App\Models\Call;
use App\Models\Device;
use App\Models\Origin;
use App\Models\PatrolAssignment;
use App\Models\Procedure;
use App\Models\ProcedureType;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProcedureLogStageWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Sector $sector;

    protected Origin $origin;

    protected ProcedureType $type;

    protected Device $device;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Administrador', 'Supervisor', 'Operador', 'Inspector'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        $this->sector = Sector::create(['name' => 'Sector Centro']);
        $this->origin = Origin::create(['name' => '1426']);
        $this->type = ProcedureType::create(['name' => 'Accidente Vehicular']);
        $this->device = Device::create(['name' => 'M-10', 'operational_status' => 'Disponible']);
    }

    protected function createProcedure(User $creator, string $status = 'En Camino'): Procedure
    {
        return Procedure::create([
            'user_id' => $creator->id,
            'origin_id' => $this->origin->id,
            'sector_id' => $this->sector->id,
            'procedure_type_id' => $this->type->id,
            'initial_procedure_type_id' => $this->type->id,
            'device_id' => $this->device->id,
            'exact_address' => 'Balmaceda 500',
            'status' => $status,
        ]);
    }

    protected function createOperator(): User
    {
        $operator = User::factory()->create();
        $operator->assignRole('Operador');
        $operator->sectors()->attach($this->sector->id);

        return $operator;
    }

    public function test_novedad_preserves_current_status_and_arrival_transitions_automatically(): void
    {
        $operator = $this->createOperator();
        $procedure = $this->createProcedure($operator, 'En Camino');

        // 1. Novedad mantiene el estado actual ("En Camino") sin permitir retroceso manual de operador
        Livewire::actingAs($operator)
            ->test(ShowProcedure::class, ['procedure' => $procedure])
            ->set('action_type', 'Novedad')
            ->set('manual_status_override', true) // Operador no tiene permiso de override manual
            ->set('status', 'Pendiente')
            ->set('description', 'Móvil informa tráfico lento en ruta.')
            ->call('saveLog')
            ->assertHasNoErrors();

        $procedure->refresh();
        $this->assertEquals('En Camino', $procedure->status);

        // 2. Llegada al lugar cambia automáticamente a "En el Lugar" y registra arrived_at
        Livewire::actingAs($operator)
            ->test(ShowProcedure::class, ['procedure' => $procedure])
            ->set('action_type', 'Llegada al lugar')
            ->set('description', 'Unidad arriba al sitio del suceso.')
            ->call('saveLog')
            ->assertHasNoErrors();

        $procedure->refresh();
        $this->assertEquals('En el Lugar', $procedure->status);
        $this->assertNotNull($procedure->arrived_at);

        // 3. Una vez En el Lugar, la opción "Llegada al lugar" ya no aparece en el formulario
        Livewire::actingAs($operator)
            ->test(ShowProcedure::class, ['procedure' => $procedure])
            ->assertDontSee('<option value="Llegada al lugar">', false);
    }

    public function test_external_derivation_requires_agency_and_supports_keeping_open_or_closing(): void
    {
        $operator = $this->createOperator();
        $procedure = $this->createProcedure($operator, 'En el Lugar');

        // 1. Sin elegir organismo externo debe fallar validación
        Livewire::actingAs($operator)
            ->test(ShowProcedure::class, ['procedure' => $procedure])
            ->set('action_type', 'Coordinación Externa (Carabineros / SAMU / Bomberos)')
            ->call('saveLog')
            ->assertHasErrors(['external_agency']);

        // 2. Derivación a Bomberos SIN cerrar -> pasa a Derivado y guarda metadata estructurada
        Livewire::actingAs($operator)
            ->test(ShowProcedure::class, ['procedure' => $procedure])
            ->set('action_type', 'Coordinación Externa (Carabineros / SAMU / Bomberos)')
            ->set('external_agency', 'DERIVADO A BOMBEROS')
            ->set('external_reference', '1ra Compañía Buin')
            ->set('close_after_derivation', false)
            ->set('description', 'Se solicita unidad de rescate.')
            ->call('saveLog')
            ->assertHasNoErrors();

        $procedure->refresh();
        $this->assertEquals('Derivado', $procedure->status);
        $this->assertEquals('DERIVADO A BOMBEROS', $procedure->derived_to);
        $this->assertEquals('1ra Compañía Buin', $procedure->external_reference);
        $this->assertNull($procedure->closed_at);

        // 3. Derivación a otro organismo con cierre inmediato -> pasa a Cerrado y guarda resolución
        Livewire::actingAs($operator)
            ->test(ShowProcedure::class, ['procedure' => $procedure])
            ->set('action_type', 'Coordinación Externa (Carabineros / SAMU / Bomberos)')
            ->set('external_agency', 'OTRO')
            ->set('custom_external_agency', 'SAPU Buin')
            ->set('close_after_derivation', true)
            ->call('saveLog')
            ->assertHasNoErrors();

        $procedure->refresh();
        $this->assertEquals('Cerrado', $procedure->status);
        $this->assertEquals('SAPU Buin', $procedure->derived_to);
        $this->assertEquals('Derivado a SAPU Buin', $procedure->closure_resolution);
        $this->assertNotNull($procedure->closed_at);

        // 4. La ficha oficial imprimible muestra la derivación y resolución de cierre
        $this->actingAs($operator)
            ->get(route('procedures.print', $procedure))
            ->assertStatus(200)
            ->assertSee('SAPU Buin')
            ->assertSee('Derivado a SAPU Buin');
    }

    public function test_closure_action_requires_resolution_and_closes_procedure(): void
    {
        $operator = $this->createOperator();
        $procedure = $this->createProcedure($operator, 'En el Lugar');

        Livewire::actingAs($operator)
            ->test(ShowProcedure::class, ['procedure' => $procedure])
            ->set('action_type', 'Cierre de Procedimiento')
            ->call('saveLog')
            ->assertHasErrors(['closure_resolution']);

        Livewire::actingAs($operator)
            ->test(ShowProcedure::class, ['procedure' => $procedure])
            ->set('action_type', 'Cierre de Procedimiento')
            ->set('closure_resolution', 'SOLUCIONADO EN EL LUGAR')
            ->set('description', 'Situación controlada sin lesionados.')
            ->call('saveLog')
            ->assertHasNoErrors();

        $procedure->refresh();
        $this->assertEquals('Cerrado', $procedure->status);
        $this->assertEquals('SOLUCIONADO EN EL LUGAR', $procedure->closure_resolution);
        $this->assertNotNull($procedure->closed_at);
    }

    public function test_field_inspector_can_derive_and_close_from_field_panel(): void
    {
        $inspector = User::factory()->create();
        $inspector->assignRole('Inspector');

        $assignment = PatrolAssignment::create([
            'date' => today()->toDateString(),
            'shift_name' => 'Turno Mañana',
            'device_id' => $this->device->id,
            'sector_id' => $this->sector->id,
            'is_active' => true,
        ]);
        $assignment->crew()->attach($inspector->id, ['role' => 'Inspector']);

        $procedure = $this->createProcedure($inspector, 'En el Lugar');
        $procedure->update(['patrol_assignment_id' => $assignment->id]);

        Livewire::actingAs($inspector)
            ->test(FieldInspectorPanel::class)
            ->call('openQuickNote', $procedure->id)
            ->set('quick_action_type', 'Derivación Externa')
            ->set('quick_external_agency', 'DERIVADO A CARABINEROS')
            ->set('quick_close_after_derivation', true)
            ->set('quick_note', 'Entregado a patrulla de 15° Comisaría.')
            ->call('saveQuickNote', $procedure->id)
            ->assertHasNoErrors();

        $procedure->refresh();
        $this->assertEquals('Cerrado', $procedure->status);
        $this->assertEquals('DERIVADO A CARABINEROS', $procedure->derived_to);
        $this->assertEquals('Derivado a DERIVADO A CARABINEROS', $procedure->closure_resolution);
        $this->assertNotNull($procedure->closed_at);
    }

    public function test_calls_report_opens_classification_note_modal_and_allows_editing(): void
    {
        $operator = $this->createOperator();

        $call = Call::create([
            'asterisk_id' => 'ast-note-modal-1',
            'caller_id' => '+56988887777',
            'extension' => '101',
            'status' => 'completed',
            'classification' => 'Información General',
            'notes' => 'Vecino consultó por horario de atención de farmacia de turno.',
        ]);

        Livewire::actingAs($operator)
            ->test(CallsReport::class)
            ->assertSee('Información General')
            ->call('openNoteModal', $call->id)
            ->assertSet('isNoteModalOpen', true)
            ->assertSee('Vecino consultó por horario de atención de farmacia de turno.')
            ->call('editClassificationFromNoteModal')
            ->assertSet('isNoteModalOpen', false)
            ->assertSet('isClassifyModalOpen', true)
            ->assertSet('selectedCallId', $call->id);
    }

    public function test_linking_call_clears_previous_classification_blocks_child_cases_and_records_note(): void
    {
        $operator = $this->createOperator();

        $parent = $this->createProcedure($operator, 'En Camino');
        $parent->update(['caller_phone' => '+56912345678']);

        $child = $this->createProcedure($operator, 'Cerrado');
        $child->update(['parent_id' => $parent->id]);

        $call = Call::create([
            'asterisk_id' => 'ast-link-improved-1',
            'caller_id' => '912345678', // Sin +56 para validar normalización de últimos 8 dígitos
            'extension' => '101',
            'status' => 'completed',
            'classification' => 'Llamada Cortada',
        ]);

        // 1. No permite amarrar a un caso hijo (anexo)
        Livewire::actingAs($operator)
            ->test(CallsReport::class)
            ->call('openLinkModal', $call->id)
            ->call('linkCallToProcedure', $child->id)
            ->assertHasErrors(['procedureSearch']);

        $this->assertNull($call->fresh()->procedure_id);

        // 2. Permite amarrar al caso principal, limpia clasificación previa y guarda nota en bitácora
        Livewire::actingAs($operator)
            ->test(CallsReport::class)
            ->call('openLinkModal', $call->id)
            ->set('linkNote', 'Vecino llama nuevamente consultando hora estimada de llegada.')
            ->call('linkCallToProcedure', $parent->id)
            ->assertHasNoErrors();

        $call->refresh();
        $this->assertSame($parent->id, $call->procedure_id);
        $this->assertNull($call->classification);
        $this->assertDatabaseHas('procedure_logs', [
            'procedure_id' => $parent->id,
            'action_type' => 'Llamada Vinculada',
        ]);
    }

    public function test_grouping_cases_prevents_chains_logs_audit_and_allows_supervisor_unlink(): void
    {
        $supervisor = User::factory()->create();
        $supervisor->assignRole('Supervisor');

        $operator = $this->createOperator();

        $parent = $this->createProcedure($supervisor, 'En el Lugar');
        $child = $this->createProcedure($supervisor, 'En Camino');
        $third = $this->createProcedure($supervisor, 'Pendiente');

        // 1. Anexar $child a $parent mediante modal/acción
        Livewire::actingAs($operator)
            ->test(ShowProcedure::class, ['procedure' => $child])
            ->call('openParentLinkModal')
            ->assertSet('isParentLinkModalOpen', true)
            ->call('linkToParent', $parent->id)
            ->assertHasNoErrors();

        $child->refresh();
        $this->assertSame($parent->id, $child->parent_id);
        $this->assertSame('Cerrado', $child->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'procedure.linked_to_parent']);

        // 2. $parent ya tiene hijos -> no puede subordinarse a $third (evita árboles de 2 niveles)
        Livewire::actingAs($operator)
            ->test(ShowProcedure::class, ['procedure' => $parent])
            ->call('linkToParent', $third->id)
            ->assertHasErrors(['parent_id_to_link']);

        $this->assertNull($parent->fresh()->parent_id);

        // 3. Operador NO puede desvincular un anexo; Supervisor SÍ puede desvincular y reabrir
        Livewire::actingAs($operator)
            ->test(ShowProcedure::class, ['procedure' => $parent])
            ->call('unlinkChildProcedure', $child->id)
            ->assertForbidden();

        Livewire::actingAs($supervisor)
            ->test(ShowProcedure::class, ['procedure' => $parent])
            ->call('unlinkChildProcedure', $child->id)
            ->assertHasNoErrors();

        $child->refresh();
        $this->assertNull($child->parent_id);
        $this->assertSame('En Camino', $child->status);
        $this->assertNull($child->closed_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'procedure.unlinked_from_parent']);
    }
}
