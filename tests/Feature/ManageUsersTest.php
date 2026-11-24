<?php

namespace Tests\Feature;

use App\Livewire\ManageUsers;
use App\Mail\UserOnboardingMail;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ManageUsersTest extends TestCase
{
    use RefreshDatabase;

    protected Sector $sectorCentro;

    protected Sector $sectorSur;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Administrador', 'Supervisor', 'Operador', 'Inspector'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        $this->sectorCentro = Sector::create(['name' => 'Sector Centro']);
        $this->sectorSur = Sector::create(['name' => 'Sector Sur']);
    }

    public function test_non_admin_cannot_access_manage_users(): void
    {
        $operator = User::factory()->create();
        $operator->assignRole('Operador');

        $this->actingAs($operator)
            ->get(route('users.manage'))
            ->assertForbidden();
    }

    public function test_admin_can_filter_users_by_search_role_and_sector(): void
    {
        $admin = User::factory()->create(['name' => 'Admin Principal', 'email' => 'admin@buinseguro.cl']);
        $admin->assignRole('Administrador');

        $operator = User::factory()->create(['name' => 'Camila Operadora', 'email' => 'camila@buinseguro.cl', 'extension' => '105']);
        $operator->assignRole('Operador');
        $operator->sectors()->attach($this->sectorCentro->id);

        $inspector = User::factory()->create(['name' => 'Diego Inspector', 'email' => 'diego@buinseguro.cl']);
        $inspector->assignRole('Inspector');

        Livewire::actingAs($admin)
            ->test(ManageUsers::class)
            ->assertSee('Camila Operadora')
            ->assertSee('Diego Inspector')
            ->set('search', '105')
            ->assertSee('Camila Operadora')
            ->assertDontSee('Diego Inspector')
            ->call('clearFilters')
            ->set('filterRole', 'Inspector')
            ->assertSee('Diego Inspector')
            ->assertDontSee('Camila Operadora')
            ->call('clearFilters')
            ->set('filterSector', (string) $this->sectorCentro->id)
            ->assertSee('Camila Operadora')
            ->assertDontSee('Diego Inspector');
    }

    public function test_creating_user_sends_onboarding_email_and_logs_audit(): void
    {
        Mail::fake();

        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $operatorRole = Role::findByName('Operador');

        Livewire::actingAs($admin)
            ->test(ManageUsers::class)
            ->call('openCreateModal')
            ->assertSet('isFormModalOpen', true)
            ->set('name', 'Sofía Torres')
            ->set('email', 'sofia.torres@buinseguro.cl')
            ->set('extension', '108')
            ->set('role_id', $operatorRole->id)
            ->set('selectedSectors', [$this->sectorCentro->id, $this->sectorSur->id])
            ->call('generateTemporaryPassword')
            ->assertSet('showPassword', true)
            ->set('sendOnboardingEmail', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('isFormModalOpen', false);

        $created = User::where('email', 'sofia.torres@buinseguro.cl')->first();
        $this->assertNotNull($created);
        $this->assertTrue($created->hasRole('Operador'));
        $this->assertCount(2, $created->sectors);

        Mail::assertSent(UserOnboardingMail::class, function (UserOnboardingMail $mail) use ($created) {
            return $mail->hasTo($created->email)
                && $mail->roleName === 'Operador'
                && ! empty($mail->temporaryPassword)
                && ! $mail->isPasswordReset
                && count($mail->sectorNames) === 2;
        });

        $this->assertDatabaseHas('audit_logs', ['action' => 'user.created']);
    }

    public function test_admin_can_reset_password_and_resend_onboarding_email(): void
    {
        Mail::fake();

        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $inspector = User::factory()->create([
            'name' => 'Patricio Terreno',
            'email' => 'patricio@buinseguro.cl',
            'password' => Hash::make('old-password-123'),
        ]);
        $inspector->assignRole('Inspector');

        Livewire::actingAs($admin)
            ->test(ManageUsers::class)
            ->call('sendOnboardingEmailToUser', $inspector->id, true)
            ->assertHasNoErrors();

        $inspector->refresh();
        $this->assertFalse(Hash::check('old-password-123', $inspector->password));

        Mail::assertSent(UserOnboardingMail::class, function (UserOnboardingMail $mail) use ($inspector) {
            return $mail->hasTo($inspector->email)
                && $mail->roleName === 'Inspector'
                && $mail->isPasswordReset === true
                && ! empty($mail->temporaryPassword);
        });

        $this->assertDatabaseHas('audit_logs', ['action' => 'user.onboarding_reset_sent']);
    }
}
