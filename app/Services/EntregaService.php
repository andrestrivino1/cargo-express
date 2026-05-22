<?php

namespace App\Services;

use App\Enums\OrdenCargueEstado;
use App\Models\OrdenCargue;
use App\Models\Referencia;
use App\Models\Tarja;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EntregaService
{
    /**
     * Crea una nueva orden de cargue con estado pendiente.
     */
    public function crearOrdenCargue(array $data): OrdenCargue
    {
        return OrdenCargue::create([
            'cliente_id' => $data['cliente_id'],
            'fecha_despacho' => $data['fecha_despacho'],
            'estado' => OrdenCargueEstado::Pendiente,
            'notas' => $data['notas'] ?? null,
        ]);
    }

    /**
     * Genera una tarja para la orden de cargue dada.
     */
    public function generarTarja(OrdenCargue $orden, array $detalles, User $despachador): Tarja
    {
        return DB::transaction(function () use ($orden, $detalles, $despachador) {
            $tarja = Tarja::create([
                'orden_cargue_id' => $orden->id,
                'despachador_id' => $despachador->id,
                'fecha_entrega' => now(),
                'observaciones' => null,
            ]);

            foreach ($detalles as $detalle) {
                $tarja->detalles()->create([
                    'referencia_id' => $detalle['referencia_id'],
                    'cantidad_entregada' => $detalle['cantidad_entregada'],
                    'ubicacion_origen_id' => $detalle['ubicacion_origen_id'],
                ]);

                $referencia = Referencia::find($detalle['referencia_id']);
                $referencia->decrement('cantidad_actual', $detalle['cantidad_entregada']);
            }

            $orden->update(['estado' => OrdenCargueEstado::Completada]);

            return $tarja;
        });
    }

    /**
     * Lista entregas (órdenes de cargue) con filtros y paginación.
     */
    public function listarEntregas(array $filtros): LengthAwarePaginator
    {
        $query = OrdenCargue::query()
            ->with(['cliente', 'despachador', 'tarjas.detalles']);

        if (!empty($filtros['fecha_desde'])) {
            $query->where('fecha_despacho', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query->where('fecha_despacho', '<=', $filtros['fecha_hasta']);
        }

        if (!empty($filtros['cliente_id'])) {
            $query->where('cliente_id', $filtros['cliente_id']);
        }

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        return $query->orderBy('created_at', 'desc')->paginate(15);
    }
}
