<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\VersionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_login_page_displays_current_semver_version_and_changelog_summary(): void
    {
        $service = app(VersionService::class);
        $versionTag = $service->getVersionTag();
        $latestRelease = $service->getLatestRelease();

        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee($versionTag);
        $response->assertSee($latestRelease['title']);
        $response->assertSee('PR #7');
    }

    public function test_profile_menu_in_navigation_displays_version_and_changelog_summary(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Operador');

        $service = app(VersionService::class);
        $versionTag = $service->getVersionTag();
        $latestRelease = $service->getLatestRelease();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Versión '.$versionTag);
        $response->assertSee($latestRelease['title']);
        $response->assertSee('Registro de Versiones (Changelog)');
    }

    public function test_artisan_version_sync_command_succeeds(): void
    {
        $this->artisan('version:sync --dry-run')
            ->assertExitCode(0);
    }
}
