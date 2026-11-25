<?php

namespace Tests\Feature;

use App\Exports\ProceduresExport;
use App\Livewire\OperatorReports;
use App\Livewire\ReportsOverview;
use App\Livewire\ShowProcedure;
use App\Models\Call;
use App\Models\Device;
use App\Models\Evidence;
use App\Models\Origin;
use App\Models\Procedure;
use App\Models\ProcedureType;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportsAndEvidencesTest extends TestCase
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
        $this->type = ProcedureType::create(['name' => 'Ruidos Molestos']);
        $this->device = Device::create(['name' => 'M-05', 'operational_status' => 'Disponible']);
    }

    public function test_operator_reports_panel_shows_own_cases_calls_and_generates_batch_pdf(): void
    {
        $operatorA = User::factory()->create(['extension' => '101']);
        $operatorA->assignRole('Operador');
        $operatorA->sectors()->attach($this->sector->id);

        $operatorB = User::factory()->create(['extension' => '102']);
        $operatorB->assignRole('Operador');
        $operatorB->sectors()->attach($this->sector->id);

        $inspector = User::factory()->create();
        $inspector->assignRole('Inspector');

        $procA1 = Procedure::create([
            'user_id' => $operatorA->id,
            'origin_id' => $this->origin->id,
            'sector_id' => $this->sector->id,
            'procedure_type_id' => $this->type->id,
            'initial_procedure_type_id' => $this->type->id,
            'device_id' => $this->device->id,
            'exact_address' => 'Balmaceda 111',
            'status' => 'Cerrado',
        ]);

        $procA2 = Procedure::create([
            'user_id' => $operatorA->id,
            'origin_id' => $this->origin->id,
            'sector_id' => $this->sector->id,
            'procedure_type_id' => $this->type->id,
            'initial_procedure_type_id' => $this->type->id,
            'exact_address' => 'San Martín 222',
            'status' => 'Pendiente',
        ]);

        Procedure::create([
            'user_id' => $operatorB->id,
            'origin_id' => $this->origin->id,
            'sector_id' => $this->sector->id,
            'procedure_type_id' => $this->type->id,
            'initial_procedure_type_id' => $this->type->id,
            'exact_address' => 'Otro Operador 999',
            'status' => 'Pendiente',
        ]);

        Call::create([
            'asterisk_id' => 'ast-op-rep-1',
            'caller_id' => '+56911112222',
            'extension' => '101',
            'status' => 'completed',
            'procedure_id' => $procA1->id,
        ]);

        // Inspector no puede entrar a Mis Gestiones
        $this->actingAs($inspector)->get(route('reports.operator'))->assertForbidden();

        // Operador A ve solo sus casos y puede seleccionar para PDF agrupado
        $component = Livewire::actingAs($operatorA)
            ->test(OperatorReports::class)
            ->assertSee('Balmaceda 111')
            ->assertSee('San Martín 222')
            ->assertDontSee('Otro Operador 999')
            ->call('selectAllFilteredProcedures');

        $selectedIds = $component->get('selectedProcedureIds');
        $this->assertEqualsCanonicalizing([$procA1->id, $procA2->id], $selectedIds);

        $component->set('activeTab', 'calls')
            ->assertSee('+56911112222');

        // Emitir PDF agrupado con ambos casos y marca de agua desactivada
        $batchResponse = $this->actingAs($operatorA)
            ->get(route('procedures.print-batch', [
                'ids' => "{$procA1->id},{$procA2->id}",
                'watermark' => '0',
            ]));

        $batchResponse->assertStatus(200)
            ->assertSee('Balmaceda 111')
            ->assertSee('San Martín 222')
            ->assertSee('watermark: false', false);

        $this->assertDatabaseHas('audit_logs', ['action' => 'procedure.export']);
    }

    public function test_reports_overview_filters_by_operator_and_exports_excel_with_all_filters(): void
    {
        Excel::fake();

        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $operator1 = User::factory()->create(['name' => 'Operador Uno']);
        $operator1->assignRole('Operador');

        $operator2 = User::factory()->create(['name' => 'Operador Dos']);
        $operator2->assignRole('Operador');

        $proc1 = Procedure::create([
            'user_id' => $operator1->id,
            'origin_id' => $this->origin->id,
            'sector_id' => $this->sector->id,
            'procedure_type_id' => $this->type->id,
            'initial_procedure_type_id' => $this->type->id,
            'device_id' => $this->device->id,
            'exact_address' => 'Calle Operador Uno 10',
            'status' => 'Cerrado',
        ]);

        Procedure::create([
            'user_id' => $operator2->id,
            'origin_id' => $this->origin->id,
            'sector_id' => $this->sector->id,
            'procedure_type_id' => $this->type->id,
            'initial_procedure_type_id' => $this->type->id,
            'exact_address' => 'Calle Operador Dos 20',
            'status' => 'Pendiente',
        ]);

        Livewire::actingAs($admin)
            ->test(ReportsOverview::class)
            ->set('user_id', $operator1->id)
            ->set('status_filter', 'Cerrado')
            ->set('device_id', $this->device->id)
            ->assertSee('Calle Operador Uno 10')
            ->assertDontSee('Calle Operador Dos 20')
            ->call('exportToExcel');

        $expectedFileName = 'Reporte_Procedimientos_'.now()->format('Y-m-d_H-i').'.xlsx';

        Excel::assertDownloaded(
            $expectedFileName,
            function (ProceduresExport $export) use ($operator1, $proc1) {
                $results = $export->query()->get();

                return (int) $export->user_id === (int) $operator1->id
                    && $export->status_filter === 'Cerrado'
                    && (int) $export->device_id === (int) $this->device->id
                    && $results->count() === 1
                    && $results->first()->id === $proc1->id;
            }
        );
    }

    public function test_show_procedure_supports_multiple_image_upload_lightbox_and_watermark_toggle(): void
    {
        Storage::fake('public');

        $operator = User::factory()->create();
        $operator->assignRole('Operador');
        $operator->sectors()->attach($this->sector->id);

        $procedure = Procedure::create([
            'user_id' => $operator->id,
            'origin_id' => $this->origin->id,
            'sector_id' => $this->sector->id,
            'procedure_type_id' => $this->type->id,
            'initial_procedure_type_id' => $this->type->id,
            'exact_address' => 'Av. O Higgins 300',
            'status' => 'En el Lugar',
        ]);

        $img1 = UploadedFile::fake()->image('evidencia-1.jpg', 640, 480);
        $img2 = UploadedFile::fake()->image('evidencia-2.png', 640, 480);

        Livewire::actingAs($operator)
            ->test(ShowProcedure::class, ['procedure' => $procedure])
            ->set('action_type', 'Novedad')
            ->set('photos', [$img1, $img2])
            ->call('saveLog')
            ->assertHasNoErrors();

        $this->assertSame(2, Evidence::where('procedure_id', $procedure->id)->count());
        $this->assertDatabaseHas('procedure_logs', [
            'procedure_id' => $procedure->id,
            'description' => 'Adjunta 2 evidencias fotográficas.',
        ]);

        // Verificar que la ficha individual respeta ?watermark=1 y ?watermark=0
        $this->actingAs($operator)
            ->get(route('procedures.print', ['procedure' => $procedure->id, 'watermark' => '1']))
            ->assertStatus(200)
            ->assertSee('watermark: true', false)
            ->assertSee('BUIN SEGURO');

        $this->actingAs($operator)
            ->get(route('procedures.print', ['procedure' => $procedure->id, 'watermark' => '0']))
            ->assertStatus(200)
            ->assertSee('watermark: false', false);
    }
}
