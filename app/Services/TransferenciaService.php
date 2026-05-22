<?php

namespace App\Services;

use App\Models\Referencia;
use App\Models\Transferencia;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TransferenciaService
{
    /**
     * Transferir productos entre módulos (ubicaciones) del mismo cliente.
     */
    public function transferirEntreModulos(array $data, User $usuario): Transferencia
    {
        $referencia = Referencia::findOrFail($data['referencia_id']);

        if (!$referencia->ubicacion_patio_id) {
            throw new InvalidArgumentException('La referencia no tiene una ubicación asignada.');
        }

        if ($referencia->cantidad_actual < $data['cantidad']) {
            throw new InvalidArgumentException('La cantidad a transferir excede la cantidad disponible.');
        }

        return DB::transaction(function () use ($data, $usuario, $referencia) {
            $ubicacionOrigenId = $referencia->ubicacion_patio_id;

            // Decrementar cantidad en la referencia origen
            $referencia->decrement('cantidad_actual', $data['cantidad']);

            // Buscar si ya existe una referencia para el mismo producto, cliente y contenedor en el destino
            $referenciaDestino = Referencia::where('producto_id', $referencia->producto_id)
                ->where('cliente_id', $referencia->cliente_id)
                ->where('contenedor_id', $referencia->contenedor_id)
                ->where('ubicacion_patio_id', $data['ubicacion_destino_id'])
                ->first();

            if ($referenciaDestino) {
                $referenciaDestino->increment('cantidad_actual', $data['cantidad']);
            } else {
                $referenciaDestino = Referencia::create([
                    'contenedor_id' => $referencia->contenedor_id,
                    'producto_id' => $referencia->producto_id,
                    'cliente_id' => $referencia->cliente_id,
                    'codigo' => $referencia->codigo,
                    'descripcion' => $referencia->descripcion,
                    'cantidad_inicial' => $data['cantidad'],
                    'cantidad_actual' => $data['cantidad'],
                    'unidad_medida' => $referencia->unidad_medida,
                    'ubicacion_patio_id' => $data['ubicacion_destino_id'],
                    'fecha_ingreso' => $referencia->fecha_ingreso,
                ]);
            }

            // Crear registro de transferencia
            $transferencia = Transferencia::create([
                'tipo' => 'entre_modulos',
                'usuario_id' => $usuario->id,
                'referencia_origen_id' => $referencia->id,
                'referencia_destino_id' => $referenciaDestino->id,
                'ubicacion_origen_id' => $ubicacionOrigenId,
                'ubicacion_destino_id' => $data['ubicacion_destino_id'],
                'cantidad' => $data['cantidad'],
            ]);

            return $transferencia;
        });
    }

    /**
     * Transferir productos entre clientes.
     */
    public function transferirEntreClientes(array $data, User $usuario): Transferencia
    {
        $referencia = Referencia::findOrFail($data['referencia_id']);

        if ($referencia->cantidad_actual < $data['cantidad']) {
            throw new InvalidArgumentException('La cantidad a transferir excede la cantidad disponible.');
        }

        if (empty($data['autorizacion_cliente'])) {
            throw new InvalidArgumentException('La autorización del cliente es requerida.');
        }

        return DB::transaction(function () use ($data, $usuario, $referencia) {
            $ubicacionOrigenId = $referencia->ubicacion_patio_id;
            $clienteOrigenId = $referencia->cliente_id;

            // Decrementar cantidad en la referencia origen
            $referencia->decrement('cantidad_actual', $data['cantidad']);

            // Crear nueva referencia para el cliente destino
            $referenciaDestino = Referencia::create([
                'contenedor_id' => $referencia->contenedor_id,
                'producto_id' => $referencia->producto_id,
                'cliente_id' => $data['cliente_destino_id'],
                'codigo' => $referencia->codigo,
                'descripcion' => $referencia->descripcion,
                'cantidad_inicial' => $data['cantidad'],
                'cantidad_actual' => $data['cantidad'],
                'unidad_medida' => $referencia->unidad_medida,
                'ubicacion_patio_id' => $data['ubicacion_destino_id'],
                'fecha_ingreso' => now(),
            ]);

            // Crear registro de transferencia
            $transferencia = Transferencia::create([
                'tipo' => 'entre_clientes',
                'usuario_id' => $usuario->id,
                'referencia_origen_id' => $referencia->id,
                'referencia_destino_id' => $referenciaDestino->id,
                'ubicacion_origen_id' => $ubicacionOrigenId,
                'ubicacion_destino_id' => $data['ubicacion_destino_id'],
                'cantidad' => $data['cantidad'],
                'cliente_origen_id' => $clienteOrigenId,
                'cliente_destino_id' => $data['cliente_destino_id'],
                'motivo' => $data['motivo'],
                'autorizacion_cliente' => $data['autorizacion_cliente'],
            ]);

            return $transferencia;
        });
    }

    /**
     * Listar transferencias con filtros y paginación.
     */
    public function listarTransferencias(array $filtros): LengthAwarePaginator
    {
        $query = Transferencia::with([
            'usuario',
            'referenciaOrigen.producto',
            'referenciaOrigen.contenedor',
            'referenciaDestino.producto',
            'referenciaDestino.contenedor',
            'ubicacionOrigen',
            'ubicacionDestino',
            'clienteOrigen',
            'clienteDestino',
        ]);

        if (!empty($filtros['tipo'])) {
            $query->where('tipo', $filtros['tipo']);
        }

        if (!empty($filtros['fecha_desde'])) {
            $query->whereDate('created_at', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query->whereDate('created_at', '<=', $filtros['fecha_hasta']);
        }

        if (!empty($filtros['cliente_id'])) {
            $query->where(function ($q) use ($filtros) {
                $q->where('cliente_origen_id', $filtros['cliente_id'])
                  ->orWhere('cliente_destino_id', $filtros['cliente_id']);
            });
        }

        return $query->orderByDesc('created_at')->paginate(15);
    }
}
