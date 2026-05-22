<?php

namespace App\Policies;

use App\Models\Solicitud;
use App\Models\User;

class SolicitudPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Solicitud $solicitud): bool
    {
        if ($user->hasAnyRole(['administrador', 'coordinador', 'gerente', 'supervisor'])) {
            return true;
        }

        return $user->id === $solicitud->cliente_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function asignar(User $user, Solicitud $solicitud): bool
    {
        return $user->hasAnyRole(['administrador', 'coordinador', 'gerente']);
    }
}