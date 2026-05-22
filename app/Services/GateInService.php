<?php

namespace App\Services;

use App\Enums\ContenedorEstado;
use App\Enums\GateEventTipo;
use App\Enums\OrdenServicioEstado;
use App\Models\Contenedor;
use App\Models\GateEvent;
use App\Models\OrdenServicio;
use App\Models\Referencia;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GateInService
{
    public function registrarIngreso(array $data, User $portero): GateEvent
    {
        return DB::transaction(function () use ($data, $portero) {
            $ordenServicio = OrdenServicio::findOrFail($data['orden_servicio_id']);

            // Find or use existing contenedor
            $contenedor = $ordenServicio->contenedor;

            if (!$contenedor) {
                $contenedor = Contenedor::create([
                    'orden_servicio_id' => $ordenServicio->id,
                    'numero' => $data['numero_contenedor'],
                    'estado' => ContenedorEstado::Solicitado,
                ]);
            }

            // Create gate event
            $gateEvent = GateEvent::create([
                'contenedor_id' => $contenedor->id,
                'tipo' => GateEventTipo::GateIn,
                'usuario_id' => $portero->id,
                'hora' => now(),
                'estado_fisico' => $data['estado_fisico'] ?? null,
                'notas' => $data['notas'] ?? null,
            ]);

            // Store photos if present
            if (!empty($data['fotos'])) {
                $gateEvent->guardarFotos($data['fotos'], "gate-events/{$gateEvent->id}/fotos");
            }

            // Store documents if present
            if (!empty($data['documentos'])) {
                $gateEvent->guardarDocumentos($data['documentos'], "gate-events/{$gateEvent->id}/documentos");
            }

            // Update contenedor
            $contenedor->placa_vehiculo = $data['placa'];
            $contenedor->fecha_ingreso = now();
            $contenedor->save();
            $contenedor->marcarEnPatio();

            // Update orden servicio estado
            $ordenServicio->estado = OrdenServicioEstado::EnEjecucion;
            $ordenServicio->save();

            // Create referencias (products inside the container)
            $clienteId = $ordenServicio->solicitud->cliente_id;
            if (!empty($data['productos'])) {
                foreach ($data['productos'] as $item) {
                    if (empty($item['producto_id']) || empty($item['cantidad'])) {
                        continue;
                    }
                    Referencia::create([
                        'contenedor_id' => $contenedor->id,
                        'cliente_id' => $clienteId,
                        'producto_id' => $item['producto_id'],
                        'codigo' => $item['codigo'] ?? 'REF-' . $contenedor->id . '-' . $item['producto_id'],
                        'descripcion' => $item['descripcion'] ?? null,
                        'cantidad_inicial' => $item['cantidad'],
                        'cantidad_actual' => $item['cantidad'],
                        'unidad_medida' => $item['unidad_medida'] ?? 'unidades',
                        'fecha_ingreso' => now(),
                    ]);
                }
            }

            return $gateEvent;
        });
    }

    public function listarPendientes(): Collection
    {
        return Contenedor::where('estado', ContenedorEstado::Solicitado)
            ->whereHas('ordenServicio', function ($query) {
                $query->where('estado', OrdenServicioEstado::Activa);
            })
            ->with(['ordenServicio.solicitud.cliente'])
            ->get();
    }
}