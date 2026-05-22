<?php

namespace App\Policies;

use App\Models\Tarja;
use App\Models\User;

class TarjaPolicy
{
    public function imprimir(User $user, Tarja $t): bool
    {
        return ! $t->tienePendientesImportacion();
    }

    public function cerrar(User $user, Tarja $t): bool
    {
        return ! $t->tienePendientesImportacion();
    }
}
