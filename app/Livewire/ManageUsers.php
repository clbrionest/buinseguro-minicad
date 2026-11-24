<?php

namespace App\Livewire;

use App\Mail\UserOnboardingMail;
use App\Models\AuditLog;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class ManageUsers extends Component
{
    use WithPagination;

    // Catálogos
    public $roles;

    public $sectors;

    // Filtros y búsqueda
    public string $search = '';

    public string $filterRole = '';

    public string $filterSector = '';

    // Estado del Modal Crear / Editar
    public bool $isFormModalOpen = false;

    public $editingId = null;

    // Campos del formulario
    public string $name = '';

    public string $email = '';

    public string $extension = '';

    public string $password = '';

    public bool $showPassword = false;

    public $role_id = '';

    public array $selectedSectors = [];

    public bool $sendOnboardingEmail = true;

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasRole('Administrador'), 403);
        $this->roles = Role::orderBy('name')->get();
        $this->sectors = Sector::orderBy('name')->get();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterRole(): void
    {
        $this->resetPage();
    }

    public function updatingFilterSector(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterRole', 'filterSector']);
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        abort_unless(auth()->user()?->hasRole('Administrador'), 403);
        $this->resetForm();
        $this->sendOnboardingEmail = true;
        $this->isFormModalOpen = true;
    }

    public function closeFormModal(): void
    {
        $this->isFormModalOpen = false;
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->reset([
            'name',
            'email',
            'extension',
            'password',
            'showPassword',
            'role_id',
            'selectedSectors',
            'editingId',
        ]);
        $this->sendOnboardingEmail = true;
        $this->resetValidation();
    }

    public function generateTemporaryPassword(): void
    {
        $upper = Str::upper(Str::random(2));
        $lower = Str::lower(Str::random(3));
        $digits = (string) random_int(100, 999);
        $this->password = "Buin-{$upper}{$digits}{$lower}";
        $this->showPassword = true;
    }

    public function selectAllSectors(): void
    {
        $this->selectedSectors = $this->sectors->pluck('id')->map(fn ($id) => (int) $id)->toArray();
    }

    public function clearSectors(): void
    {
        $this->selectedSectors = [];
    }

    public function getSelectedRoleNameProperty(): ?string
    {
        if (! $this->role_id) {
            return null;
        }

        return $this->roles->firstWhere('id', (int) $this->role_id)?->name;
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->hasRole('Administrador'), 403);

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$this->editingId,
            'extension' => 'nullable|string|max:20',
            'role_id' => 'required|exists:roles,id',
            'selectedSectors' => 'array',
        ];

        if (! $this->editingId) {
            $rules['password'] = 'required|min:6';
        } elseif ($this->password !== '') {
            $rules['password'] = 'min:6';
        }

        $this->validate($rules);

        $isUpdate = (bool) $this->editingId;
        $plainPassword = trim($this->password) !== '' ? trim($this->password) : null;

        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);
            $user->update([
                'name' => trim($this->name),
                'email' => trim($this->email),
                'extension' => trim($this->extension) !== '' ? trim($this->extension) : null,
            ]);
            if ($plainPassword) {
                $user->update(['password' => Hash::make($plainPassword)]);
            }
        } else {
            $user = User::create([
                'name' => trim($this->name),
                'email' => trim($this->email),
                'extension' => trim($this->extension) !== '' ? trim($this->extension) : null,
                'password' => Hash::make($plainPassword),
            ]);
        }

        $role = Role::findById((int) $this->role_id);
        $user->syncRoles([$role]);

        // Sincronizar sectores (Operador usa sectores explícitos; Admin/Supervisor tienen cobertura global)
        $sectorsToSync = in_array($role->name, ['Administrador', 'Supervisor'], true)
            ? []
            : array_map('intval', $this->selectedSectors);
        $user->sectors()->sync($sectorsToSync);

        AuditLog::record(
            $isUpdate ? 'user.updated' : 'user.created',
            'Usuarios y Roles',
            ($isUpdate ? 'Actualización' : 'Creación')." de usuario {$user->name} ({$user->email}) con rol {$role->name}.",
            $user,
            ['role' => $role->name, 'extension' => $user->extension, 'sectors' => $sectorsToSync]
        );

        $flashMessage = $isUpdate ? 'Usuario actualizado correctamente.' : 'Usuario creado exitosamente.';

        if ($this->sendOnboardingEmail) {
            $mailSent = $this->dispatchOnboardingMail($user, $role->name, $plainPassword, $isUpdate && ! is_null($plainPassword));
            $flashMessage .= $mailSent
                ? ' Se envió el correo de bienvenida (onboarding).'
                : ' Sin embargo, no se pudo enviar el correo de bienvenida (revisa la configuración SMTP).';
        }

        $this->closeFormModal();
        session()->flash('message', $flashMessage);
    }

    public function edit(int $id): void
    {
        abort_unless(auth()->user()?->hasRole('Administrador'), 403);
        $user = User::with('roles', 'sectors')->findOrFail($id);
        $this->resetValidation();
        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->extension = $user->extension ?? '';
        $this->password = '';
        $this->showPassword = false;
        $this->role_id = $user->roles->first()->id ?? '';
        $this->selectedSectors = $user->sectors->pluck('id')->map(fn ($sid) => (int) $sid)->toArray();
        $this->sendOnboardingEmail = false;
        $this->isFormModalOpen = true;
    }

    public function sendOnboardingEmailToUser(int $id, bool $resetPassword = false): void
    {
        abort_unless(auth()->user()?->hasRole('Administrador'), 403);

        $user = User::with('roles', 'sectors')->findOrFail($id);
        $roleName = $user->roles->first()?->name ?? 'Usuario';
        $newPlainPassword = null;

        if ($resetPassword) {
            $upper = Str::upper(Str::random(2));
            $lower = Str::lower(Str::random(3));
            $digits = (string) random_int(100, 999);
            $newPlainPassword = "Buin-{$upper}{$digits}{$lower}";
            $user->update(['password' => Hash::make($newPlainPassword)]);
        }

        $sent = $this->dispatchOnboardingMail($user, $roleName, $newPlainPassword, $resetPassword);

        AuditLog::record(
            $resetPassword ? 'user.onboarding_reset_sent' : 'user.onboarding_sent',
            'Usuarios y Roles',
            ($resetPassword ? 'Restablecimiento de clave y envío' : 'Reenvío')." de correo de bienvenida a {$user->name} ({$user->email}).",
            $user,
            ['mail_sent' => $sent, 'password_reset' => $resetPassword]
        );

        if ($sent) {
            session()->flash('message', $resetPassword
                ? "Se generó una nueva contraseña temporal y se envió el correo de bienvenida a {$user->email}."
                : "Correo de bienvenida reenviado exitosamente a {$user->email}.");
        } else {
            session()->flash('error', "No se pudo enviar el correo a {$user->email}. Revisa la configuración de correo del servidor.");
        }
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()?->hasRole('Administrador'), 403);

        if (auth()->id() == $id) {
            session()->flash('error', 'No puedes eliminar tu propia cuenta.');

            return;
        }

        $target = User::find($id);
        if ($target) {
            AuditLog::record(
                'user.deleted',
                'Usuarios y Roles',
                "Eliminación de usuario {$target->name} ({$target->email}).",
                $target
            );
            $target->delete();
        }

        session()->flash('message', 'Usuario eliminado correctamente.');
    }

    protected function dispatchOnboardingMail(User $user, string $roleName, ?string $temporaryPassword, bool $isPasswordReset = false): bool
    {
        try {
            $sectorNames = $user->sectors()->orderBy('name')->pluck('name')->toArray();

            Mail::to($user->email)->send(new UserOnboardingMail(
                user: $user,
                roleName: $roleName,
                temporaryPassword: $temporaryPassword,
                sectorNames: $sectorNames,
                isPasswordReset: $isPasswordReset,
            ));

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    public function render()
    {
        $term = trim($this->search);

        $usersQuery = User::with('roles', 'sectors')
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('extension', 'like', "%{$term}%");
                });
            })
            ->when($this->filterRole !== '', function ($q) {
                $q->whereHas('roles', fn ($r) => $r->where('name', $this->filterRole));
            })
            ->when($this->filterSector !== '', function ($q) {
                $q->whereHas('sectors', fn ($s) => $s->where('sectors.id', (int) $this->filterSector));
            })
            ->orderBy('name');

        $roleCounts = [
            'total' => User::count(),
            'Administrador' => User::role('Administrador')->count(),
            'Supervisor' => User::role('Supervisor')->count(),
            'Operador' => User::role('Operador')->count(),
            'Inspector' => User::role('Inspector')->count(),
        ];

        return view('livewire.manage-users', [
            'users' => $usersQuery->paginate(15),
            'roleCounts' => $roleCounts,
        ])->layout('layouts.app');
    }
}
