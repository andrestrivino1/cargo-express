<?php

namespace App\Policies;

use App\Models\Referencia;
use App\Models\User;

class ReferenciaPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Referencia $referencia): bool
    {
        if ($user->hasAnyRole(['administrador', 'supervisor', 'gerente', 'operador'])) {
            return true;
        }

        return $user->id === $referencia->cliente_id;
    }
}