<?php

namespace App\Policies;

use App\Models\Contenedor;
use App\Models\User;

class ContenedorPolicy
{
    /**
     * Acciones operativas bloqueadas mientras el contenedor tenga campos
     * PENDIENTE_HISTORICO vivos (FR-022, contracts/pending-fields.md §Bloqueos).
     */
    public function gateIn(User $user, Contenedor $c): bool
    {
        return ! $c->tienePendientesImportacion();
    }

    public function gateOut(User $user, Contenedor $c): bool
    {
        return ! $c->tienePendientesImportacion();
    }

    public function programarVaciado(User $user, Contenedor $c): bool
    {
        return ! $c->tienePendientesImportacion()
            && ! $c->ordenServicio?->tienePendientesImportacion();
    }
}
