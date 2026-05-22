<?php

namespace App\Policies;

use App\Models\OrdenServicio;
use App\Models\User;

class OrdenServicioPolicy
{
    public function crearGateEvent(User $user, OrdenServicio $os): bool
    {
        return ! $os->tienePendientesImportacion();
    }
}
