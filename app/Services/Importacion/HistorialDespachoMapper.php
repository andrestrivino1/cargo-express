<?php

namespace App\Services\Importacion;

use App\Enums\OrdenCargueEstado;
use App\Models\ImportBatch;
use App\Models\OrdenCargue;
use App\Models\Referencia;
use App\Models\Tarja;
use App\Models\TarjaDetalle;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Crea las OrdenCargue + Tarja + TarjaDetalle retroactivas a partir de los
 * pares (FECHA DE DESPACHO, DESPACHO) extraídos por RowValidator.
 *
 * Importante: NO descuenta cantidad_actual de la Referencia (FR-030) —
 * el saldo persistido viene del Excel ya neto de estos despachos.
 */
final class HistorialDespachoMapper
{
    public function __construct(
        private readonly PendingFieldsRegistrar $pending,
    ) {}

    /**
     * @param  array<int, array{fecha:CarbonImmutable, cantidad:int}>  $pares
     * @return array<int, Tarja>
     */
    public function crearHistorial(Referencia $ref, User $cliente, array $pares, ImportBatch $batch): array
    {
        $creadas = [];
        foreach ($pares as $par) {
            if ($par['cantidad'] <= 0) {
                continue;
            }

            $ordenCargue = OrdenCargue::create([
                'cliente_id' => $cliente->getKey(),
                'despachador_id' => null,
                'fecha_despacho' => $par['fecha'],
                'estado' => OrdenCargueEstado::Completada,
                'notas' => null,
                'import_batch_id' => $batch->getKey(),
            ]);
            $this->pending->registrar($ordenCargue, ['despachador_id', 'notas'], $batch, prioridad: 40);

            $tarja = Tarja::create([
                'orden_cargue_id' => $ordenCargue->getKey(),
                'despachador_id' => $batch->usuario_id, // placeholder mientras se completa
                'fecha_entrega' => $par['fecha'],
                'observaciones' => null,
                'import_batch_id' => $batch->getKey(),
            ]);
            $this->pending->registrar(
                $tarja,
                ['despachador_id', 'observaciones', 'vehiculo', 'conductor'],
                $batch,
                prioridad: 50,
            );

            TarjaDetalle::create([
                'tarja_id' => $tarja->getKey(),
                'referencia_id' => $ref->getKey(),
                'cantidad_entregada' => $par['cantidad'],
                'ubicacion_origen_id' => $ref->ubicacion_patio_id,
            ]);

            $creadas[] = $tarja;
        }

        return $creadas;
    }
}
