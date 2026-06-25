<?php

namespace App\Services;

use App\Enums\MovimientoTipo;
use App\Models\MovimientoInventario;
use App\Models\Referencia;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class MovimientoInventarioService
{
    /**
     * Registra una entrada de inventario en el ledger.
     *
     * Asume que la referencia ya tiene su cantidad_actual actualizada.
     */
    public function registrarEntrada(
        Referencia $referencia,
        int $cantidad,
        User $usuario,
        ?Model $documentable = null,
        ?string $observaciones = null
    ): MovimientoInventario {
        return $this->registrar(MovimientoTipo::Entrada, $referencia, $cantidad, $usuario, $documentable, $observaciones);
    }

    /**
     * Registra una salida de inventario en el ledger.
     *
     * Asume que la referencia ya tiene su cantidad_actual actualizada (descontada).
     */
    public function registrarSalida(
        Referencia $referencia,
        int $cantidad,
        User $usuario,
        ?Model $documentable = null,
        ?string $observaciones = null
    ): MovimientoInventario {
        return $this->registrar(MovimientoTipo::Salida, $referencia, $cantidad, $usuario, $documentable, $observaciones);
    }

    private function registrar(
        MovimientoTipo $tipo,
        Referencia $referencia,
        int $cantidad,
        User $usuario,
        ?Model $documentable,
        ?string $observaciones
    ): MovimientoInventario {
        return $referencia->movimientos()->create([
            'tipo' => $tipo,
            'cantidad' => $cantidad,
            'saldo_resultante' => $referencia->cantidad_actual,
            'usuario_id' => $usuario->id,
            'documentable_type' => $documentable ? $documentable->getMorphClass() : null,
            'documentable_id' => $documentable?->getKey(),
            'observaciones' => $observaciones,
        ]);
    }
}
