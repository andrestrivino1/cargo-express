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

    /**
     * Registra una corrección de la cantidad declarada de una referencia.
     *
     * El tipo lo decide el signo del delta, no el llamador: la columna `cantidad`
     * es unsigned, así que la dirección del movimiento vive en el tipo y no en el
     * signo del número. Un delta de 0 no escribe nada.
     *
     * Asume que la referencia ya tiene su cantidad_actual actualizada.
     */
    public function registrarAjuste(
        Referencia $referencia,
        int $delta,
        User $usuario,
        ?Model $documentable = null,
        ?string $observaciones = null
    ): ?MovimientoInventario {
        if ($delta === 0) {
            return null;
        }

        $tipo = $delta > 0 ? MovimientoTipo::AjustePositivo : MovimientoTipo::AjusteNegativo;

        return $this->registrar($tipo, $referencia, abs($delta), $usuario, $documentable, $observaciones);
    }

    /**
     * Registra la baja del disponible de una referencia que se retira del
     * inventario. No cuelga de ningún documento operativo: el retiro es una
     * corrección administrativa, y el motivo viaja en las observaciones.
     *
     * Asume que la referencia ya tiene su cantidad_actual en 0.
     */
    public function registrarBaja(
        Referencia $referencia,
        int $cantidad,
        User $usuario,
        ?string $motivo = null
    ): MovimientoInventario {
        return $this->registrar(MovimientoTipo::Baja, $referencia, $cantidad, $usuario, null, $motivo);
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
