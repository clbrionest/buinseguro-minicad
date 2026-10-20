<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;
use App\Models\Sector;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class ManageUsers extends Component
{
    // Datos de los catálogos
    public $roles, $sectors;

    // Variables del formulario
    public $name = '';
    public $email = '';
    public $extension = '';
    public $password = '';
    public $role_id = '';
    public $selectedSectors = []; // Aquí se guardarán los IDs de los checkboxes
    public $editingId = null;

    public function mount()
    {
        abort_unless(auth()->user()?->hasRole('Administrador'), 403);
        $this->roles = Role::all();
        $this->sectors = Sector::orderBy('name')->get();
    }

    public function resetForm()
    {
        $this->reset(['name', 'email', 'extension', 'password', 'role_id', 'selectedSectors', 'editingId']);
        $this->resetValidation();
    }

    public function save()
    {
        abort_unless(auth()->user()?->hasRole('Administrador'), 403);

        // 1. Validaciones dinámicas (la contraseña no es obligatoria si estamos editando)
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->editingId,
            'extension' => 'nullable|string|max:20',
            'role_id' => 'required',
            'selectedSectors' => 'array'
        ];

        if (!$this->editingId) {
            $rules['password'] = 'required|min:6';
        }

        $this->validate($rules);

        // 2. Crear o Actualizar el Usuario
        $isUpdate = (bool) $this->editingId;
        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);
            $user->update([
                'name' => $this->name,
                'email' => $this->email,
                'extension' => $this->extension ?: null,
            ]);
            // Solo actualizamos contraseña si escribió una nueva
            if ($this->password) {
                $user->update(['password' => Hash::make($this->password)]);
            }
            session()->flash('message', 'Usuario actualizado correctamente.');
        } else {
            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'extension' => $this->extension ?: null,
                'password' => Hash::make($this->password),
            ]);
            session()->flash('message', 'Usuario creado exitosamente.');
        }

        // 3. Asignar el Rol de Spatie (Sync reemplaza el rol anterior si tenía uno)
        $role = Role::findById($this->role_id);
        $user->syncRoles([$role]);

        // 4. Asignar Sectores en la tabla pivote (Magia de Eloquent)
        $user->sectors()->sync($this->selectedSectors);

        \App\Models\AuditLog::record(
            $isUpdate ? 'user.updated' : 'user.created',
            'Usuarios y Roles',
            ($isUpdate ? 'Actualización' : 'Creación') . " de usuario {$user->name} ({$user->email}) con rol {$role->name}.",
            $user,
            ['role' => $role->name, 'extension' => $user->extension, 'sectors' => $this->selectedSectors]
        );

        $this->resetForm();
    }

    public function edit($id)
    {
        abort_unless(auth()->user()?->hasRole('Administrador'), 403);
        $user = User::with('roles', 'sectors')->findOrFail($id);
        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->extension = $user->extension ?? '';
        // Asignamos el rol actual (asumiendo 1 rol por usuario)
        $this->role_id = $user->roles->first()->id ?? '';
        // Extraemos solo los IDs de los sectores para marcar los checkboxes
        $this->selectedSectors = $user->sectors->pluck('id')->toArray();
    }

    public function delete($id)
    {
        abort_unless(auth()->user()?->hasRole('Administrador'), 403);

        // Medida de seguridad básica: no auto-eliminarse
        if (auth()->id() == $id) {
            session()->flash('error', 'No puedes eliminar tu propia cuenta.');
            return;
        }
        $target = User::find($id);
        if ($target) {
            \App\Models\AuditLog::record(
                'user.deleted',
                'Usuarios y Roles',
                "Eliminación de usuario {$target->name} ({$target->email}).",
                $target
            );
            $target->delete();
        }
        session()->flash('message', 'Usuario eliminado.');
    }

    public function render()
    {
        // Traemos los usuarios con sus roles y sectores para no saturar la BD
        $users = User::with('roles', 'sectors')->orderBy('name')->get();

        return view('livewire.manage-users', [
            'users' => $users
        ])->layout('layouts.app');
    }
}