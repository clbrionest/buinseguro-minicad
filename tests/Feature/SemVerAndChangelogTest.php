<?php

namespace Tests\Feature;

use App\Console\Commands\ResetProceduresForLaunchCommand;
use App\Models\Call;
use App\Models\Device;
use App\Models\Evidence;
use App\Models\Origin;
use App\Models\Procedure;
use App\Models\ProcedureLog;
use App\Models\ProcedureType;
use App\Models\Sector;
use App\Models\User;
use App\Services\VersionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SemVerAndChangelogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Administrador', 'Supervisor', 'Operador', 'Inspector'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }
    }

    public function test_semver_parsing_and_bumping_rules(): void
    {
        $service = new VersionService;

        $this->assertEquals(['major' => 1, 'minor' => 3, 'patch' => 0], $service->parseSemVer('v1.3.0'));
        $this->assertEquals('1.3.1', $service->bumpSemVer('1.3.0', 'patch'));
        $this->assertEquals('1.4.0', $service->bumpSemVer('1.3.5', 'minor'));
        $this->assertEquals('2.0.0', $service->bumpSemVer('1.4.2', 'major'));
    }

    public function test_determine_bump_type_from_commits_and_prs(): void
    {
        $service = new VersionService;

        $prEntry = $service->parseCommitEntry(
            '7c683b4',
            '2026-11-16',
            'Merge pull request #7 from clbrionest/chore/auto-deploy',
            'Configuraciones de entorno y mejoras en validaciones'
        );

        $this->assertNotNull($prEntry);
        $this->assertEquals('PR #7', $prEntry['pr_ref']);
        $this->assertEquals('chore', $prEntry['type']);
        $this->assertStringContainsString('PR #7', $prEntry['description']);

        $featEntry = $service->parseCommitEntry(
            '2b7d66d',
            '2026-11-17',
            'feat(mapa): nuevo mapa operativo táctico'
        );

        $this->assertNotNull($featEntry);
        $this->assertEquals('feat', $featEntry['type']);
        $this->assertFalse($featEntry['breaking']);

        $this->assertEquals('patch', $service->determineBumpType([$prEntry]));
        $this->assertEquals('minor', $service->determineBumpType([$prEntry, $featEntry]));

        $breakingEntry = $service->parseCommitEntry(
            'abc1234',
            '2026-11-17',
            'feat(api)!: cambio incompatible de esquema'
        );
        $this->assertEquals('major', $service->determineBumpType([$featEntry, $breakingEntry]));
    }

    public function test_login_page_displays_only_semver_version_without_changelog_details(): void
    {
        $service = app(VersionService::class);
        $versionTag = $service->getVersionTag();
        $latestRelease = $service->getLatestRelease();

        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee($versionTag);
        $response->assertDontSee($latestRelease['title']);
        $response->assertDontSee('Ver historial completo de cambios');
    }

    public function test_version_details_and_changelog_are_visible_only_to_administrators(): void
    {
        $operator = User::factory()->create(['extension' => '101']);
        $operator->assignRole('Operador');

        $admin = User::factory()->create(['extension' => '200']);
        $admin->assignRole('Administrador');

        $service = app(VersionService::class);
        $versionTag = $service->getVersionTag();
        $latestRelease = $service->getLatestRelease();

        // Operador solo ve el número de versión, NO los detalles ni el Changelog
        $operatorResponse = $this->actingAs($operator)->get(route('dashboard'));
        $operatorResponse->assertStatus(200);
        $operatorResponse->assertSee($versionTag);
        $operatorResponse->assertSee('Anexo 101');
        $operatorResponse->assertSee('Alertas de Llamadas');
        $operatorResponse->assertDontSee($latestRelease['title']);
        $operatorResponse->assertDontSee('Registro de Versiones (Changelog)');

        // Administrador sí ve la versión y el detalle completo del Changelog
        $adminResponse = $this->actingAs($admin)->get(route('dashboard'));
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('Versión '.$versionTag);
        $adminResponse->assertSee($latestRelease['title']);
        $adminResponse->assertSee('Registro de Versiones (Changelog)');
    }

    public function test_one_time_launch_procedure_reset_command_clears_only_procedures_and_locks_itself(): void
    {
        $lockPath = ResetProceduresForLaunchCommand::lockFilePath();
        File::delete($lockPath);

        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $sector = Sector::create(['name' => 'Sector Centro']);
        $origin = Origin::create(['name' => '1426']);
        $type = ProcedureType::create(['name' => 'Ruidos Molestos']);
        $device = Device::create(['name' => 'M-01', 'operational_status' => 'Disponible']);

        $procedure = Procedure::create([
            'user_id' => $admin->id,
            'origin_id' => $origin->id,
            'sector_id' => $sector->id,
            'procedure_type_id' => $type->id,
            'initial_procedure_type_id' => $type->id,
            'device_id' => $device->id,
            'exact_address' => 'Balmaceda 100',
            'status' => 'En Camino',
        ]);

        ProcedureLog::create([
            'procedure_id' => $procedure->id,
            'user_id' => $admin->id,
            'action_type' => 'Ingreso',
            'description' => 'Registro de prueba previo a lanzamiento.',
        ]);

        Evidence::create([
            'procedure_id' => $procedure->id,
            'user_id' => $admin->id,
            'file_path' => 'evidences/test-launch-reset.jpg',
        ]);

        $call = Call::create([
            'asterisk_id' => 'ast-launch-reset-1',
            'caller_id' => '+56911112222',
            'extension' => '101',
            'status' => 'completed',
            'procedure_id' => $procedure->id,
        ]);

        // 1. Dry-run no borra nada ni crea el lock
        $this->artisan('minicad:reset-procedures-for-launch --dry-run')
            ->assertExitCode(0);
        $this->assertDatabaseCount('procedures', 1);
        $this->assertFileDoesNotExist($lockPath);

        // 2. Ejecución real con --confirm-reset deja procedimientos en 0 y conserva todos los mantenedores
        $this->artisan('minicad:reset-procedures-for-launch --confirm-reset')
            ->assertExitCode(0);

        $this->assertDatabaseCount('procedures', 0);
        $this->assertDatabaseCount('procedure_logs', 0);
        $this->assertDatabaseCount('evidence', 0);
        $this->assertDatabaseCount('calls', 0);

        // Los mantenedores permanecen intactos
        $this->assertDatabaseHas('sectors', ['name' => 'Sector Centro']);
        $this->assertDatabaseHas('origins', ['name' => '1426']);
        $this->assertDatabaseHas('procedure_types', ['name' => 'Ruidos Molestos']);
        $this->assertDatabaseHas('devices', ['name' => 'M-01']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'system.procedures_launch_reset']);

        // 3. Una segunda ejecución queda estrictamente bloqueada (uso único)
        $this->artisan('minicad:reset-procedures-for-launch --confirm-reset')
            ->assertExitCode(1);

        File::delete($lockPath);
    }

    public function test_artisan_version_sync_command_succeeds(): void
    {
        $this->artisan('version:sync --dry-run')
            ->assertExitCode(0);
    }
}
