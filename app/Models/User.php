<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'extension', 'theme_preference'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relación: Un usuario puede operar en varios sectores (Muchos a Muchos)
    public function sectors()
    {
        return $this->belongsToMany(Sector::class);
    }

    // Relación: Un usuario (operadora) registra muchos procedimientos (1:N)
    public function procedures()
    {
        return $this->hasMany(Procedure::class);
    }

    /**
     * Determina si el usuario tiene únicamente el rol de Inspector (sin roles de sala/supervisión).
     */
    public function isInspectorOnly(): bool
    {
        return $this->hasRole('Inspector') && ! $this->hasAnyRole(['Administrador', 'Supervisor', 'Operador']);
    }

    /**
     * Obtiene los IDs de los móviles/vehículos asignados al usuario en turnos activos.
     *
     * @return array<int, int>
     */
    public function assignedDeviceIds(): array
    {
        $fromShifts = PatrolAssignment::where('is_active', true)
            ->whereHas('crew', fn ($q) => $q->where('users.id', $this->id))
            ->pluck('device_id')
            ->toArray();

        $fromPatrols = PreventivePatrol::where('status', 'En Curso')
            ->where('user_id', $this->id)
            ->pluck('device_id')
            ->toArray();

        return array_values(array_unique(array_map('intval', array_merge($fromShifts, $fromPatrols))));
    }

    /**
     * Verifica si el usuario está autorizado para visualizar u operar un procedimiento específico.
     */
    public function canAccessProcedure(Procedure $procedure): bool
    {
        if ($this->hasAnyRole(['Administrador', 'Supervisor'])) {
            return true;
        }

        if ($this->hasRole('Operador')) {
            $allowedSectors = $this->sectors()->pluck('sectors.id')->map(fn ($id) => (int) $id)->toArray();

            return in_array((int) $procedure->sector_id, $allowedSectors, true);
        }

        if ($this->hasRole('Inspector')) {
            $assignedDevices = $this->assignedDeviceIds();
            if (! empty($procedure->device_id) && in_array((int) $procedure->device_id, $assignedDevices, true)) {
                return true;
            }

            if ($procedure->patrol_assignment_id) {
                return PatrolAssignment::where('id', $procedure->patrol_assignment_id)
                    ->whereHas('crew', fn ($q) => $q->where('users.id', $this->id))
                    ->exists();
            }

            return false;
        }

        return false;
    }
}
