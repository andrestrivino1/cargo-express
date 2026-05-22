<?php

namespace App\Services\Importacion;

use App\Enums\PendingFieldCatalog;
use App\Models\ImportBatch;
use App\Models\ImportPendingRecord;
use Illuminate\Database\Eloquent\Model;

/**
 * Registra los campos faltantes de un registro importado.
 * Centraliza el patrón "PENDIENTE_HISTORICO" para que ningún caller
 * tenga que conocer la tabla import_pending_records directamente (DRY).
 */
final class PendingFieldsRegistrar
{
    /**
     * @param  string[]  $campos  claves del catálogo del tipo de $modelo
     */
    public function registrar(Model $modelo, array $campos, ImportBatch $batch, int $prioridad = 50): ImportPendingRecord
    {
        PendingFieldCatalog::validarCampos($modelo::class, $campos);

        return ImportPendingRecord::create([
            'pendienteable_type' => $modelo::class,
            'pendienteable_id' => $modelo->getKey(),
            'import_batch_id' => $batch->getKey(),
            'campos_pendientes' => array_values($campos),
            'prioridad' => $prioridad,
        ]);
    }
}
