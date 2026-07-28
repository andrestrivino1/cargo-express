<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

/**
 * Punto único de verdad sobre qué roles pueden asignarse.
 *
 * Los roles "retirados" (config/roles.php) siguen existiendo en base de datos con
 * todos sus permisos y sus asignaciones actuales: simplemente dejan de ofrecerse
 * al crear o editar un usuario. Quien ya los tiene sigue operando igual.
 */
class RolesDisponibles
{
    /**
     * @return array<int, string>
     */
    public function retirados(): array
    {
        return config('roles.retirados', []);
    }

    public function esRetirado(string $rol): bool
    {
        return in_array($rol, $this->retirados(), true);
    }

    /**
     * Roles que un administrador puede asignar hoy.
     *
     * @return Collection<int, Role>
     */
    public function asignables(): Collection
    {
        return Role::whereNotIn('name', $this->retirados())
            ->orderBy('name')
            ->get();
    }
}
