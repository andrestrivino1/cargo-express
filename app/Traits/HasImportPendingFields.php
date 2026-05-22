<?php

namespace App\Traits;

use App\Models\ImportPendingRecord;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Modela el patrón "importar ahora, completar al consultar" (research.md §R3).
 * Cualquier entidad importada con campos PENDIENTE_HISTORICO usa este trait;
 * la fuente de verdad es la existencia de un ImportPendingRecord vivo
 * (completado_at IS NULL).
 */
trait HasImportPendingFields
{
    public static function bootHasImportPendingFields(): void
    {
        // Gancho reservado para US2/US3 (auditoría, eventos al completar, etc.).
    }

    public function pendientesImportacion(): MorphMany
    {
        return $this->morphMany(ImportPendingRecord::class, 'pendienteable');
    }

    public function pendienteImportacionActivo(): MorphOne
    {
        return $this->morphOne(ImportPendingRecord::class, 'pendienteable')
            ->whereNull('completado_at');
    }

    public function tienePendientesImportacion(): bool
    {
        return $this->pendientesImportacion()->vivos()->exists();
    }

    /** @return string[] */
    public function camposPendientesImportacion(): array
    {
        $vivo = $this->pendientesImportacion()->vivos()->first();

        return $vivo?->campos_pendientes ?? [];
    }
}
