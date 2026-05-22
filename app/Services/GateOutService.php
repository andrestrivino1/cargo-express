<?php

namespace App\Services;

use App\Enums\ContenedorEstado;
use App\Enums\GateEventTipo;
use App\Enums\OrdenServicioEstado;
use App\Enums\SolicitudEstado;
use App\Models\Contenedor;
use App\Models\GateEvent;
use App\Models\User;
use App\Notifications\TirillaGateOutNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GateOutService
{
    /**
     * Registra la limpieza y destino de salida del contenedor.
     */
    public function registrarLimpieza(Contenedor $contenedor, bool $limpiado, string $destino): void
    {
        $contenedor->update([
            'limpieza_registrada' => $limpiado,
            'destino_salida' => $destino,
        ]);
    }

    /**
     * Registra la salida (Gate Out) del contenedor.
     */
    public function registrarSalida(Contenedor $contenedor, array $data, User $portero): GateEvent
    {
        // Create GateEvent tipo=gate_out
        $gateEvent = GateEvent::create([
            'contenedor_id' => $contenedor->id,
            'tipo' => GateEventTipo::GateOut,
            'usuario_id' => $portero->id,
            'hora' => now(),
            'notas' => $data['notas'] ?? null,
        ]);

        // Store photos via HasPhotos
        if (!empty($data['fotos'])) {
            $gateEvent->guardarFotos($data['fotos'], "gate-events/{$gateEvent->id}");
        }

        // Mark contenedor as fuera de patio and set fecha_salida
        $contenedor->fecha_salida = now();
        $contenedor->save();
        $contenedor->marcarFueraDePatio();

        // Update orden_servicio estado to completada
        $ordenServicio = $contenedor->ordenServicio;
        if ($ordenServicio) {
            $ordenServicio->estado = OrdenServicioEstado::Completada;
            $ordenServicio->save();

            // Update solicitud estado to completada
            $solicitud = $ordenServicio->solicitud;
            if ($solicitud) {
                $solicitud->estado = SolicitudEstado::Completada;
                $solicitud->save();

                // Dispatch notification to client
                $cliente = $solicitud->cliente;
                if ($cliente) {
                    $cliente->notify(new TirillaGateOutNotification($contenedor));
                }
            }
        }

        return $gateEvent;
    }

    /**
     * Lista salidas con filtros y paginación.
     */
    public function listarSalidas(array $filtros): LengthAwarePaginator
    {
        $query = Contenedor::where('estado', ContenedorEstado::FueraDePatio)
            ->with(['ordenServicio.solicitud.cliente', 'gateEvents' => function ($q) {
                $q->where('tipo', GateEventTipo::GateOut)->with('usuario');
            }]);

        if (!empty($filtros['fecha_desde'])) {
            $query->where('fecha_salida', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query->where('fecha_salida', '<=', $filtros['fecha_hasta'] . ' 23:59:59');
        }

        if (!empty($filtros['cliente_id'])) {
            $query->whereHas('ordenServicio.solicitud', function ($q) use ($filtros) {
                $q->where('cliente_id', $filtros['cliente_id']);
            });
        }

        if (!empty($filtros['destino'])) {
            $query->where('destino_salida', 'like', '%' . $filtros['destino'] . '%');
        }

        return $query->orderBy('fecha_salida', 'desc')->paginate(15);
    }
}