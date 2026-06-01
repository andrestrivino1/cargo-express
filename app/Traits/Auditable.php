<?php

namespace App\Traits;

use App\Models\CambioAuditoria;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Auditable
{
    /**
     * Historial de cambios de auditoría del registro, más recientes primero.
     */
    public function cambiosAuditoria(): MorphMany
    {
        return $this->morphMany(CambioAuditoria::class, 'auditable')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }
}
