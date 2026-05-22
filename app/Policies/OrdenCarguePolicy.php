<?php

namespace App\Policies;

use App\Models\OrdenCargue;
use App\Models\User;

class OrdenCarguePolicy
{
    public function procesar(User $user, OrdenCargue $oc): bool
    {
        return ! $oc->tienePendientesImportacion();
    }
}
