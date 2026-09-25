<?php

namespace App\Enums;

enum MovimientoTipo: string
{
    case Entrada = 'entrada';
    case Salida = 'salida';

    /**
     * Corrección al alza de la cantidad declarada en el ingreso (feature 010).
     * No es mercancía que llegue: es papeleo que se corrige, por eso no reutiliza
     * Entrada — el reporte de Ingresos filtra por tipo exacto y se inflaría.
     */
    case AjustePositivo = 'ajuste_positivo';

    /** Corrección a la baja de la cantidad declarada en el ingreso (feature 010). */
    case AjusteNegativo = 'ajuste_negativo';

    /**
     * Retiro de la referencia del inventario vigente (feature 010). Descuenta el
     * disponible que quedaba para que las existencias sigan cuadrando con la
     * suma de movimientos.
     */
    case Baja = 'baja';

    public function label(): string
    {
        return match ($this) {
            self::Entrada => 'Entrada',
            self::Salida => 'Salida',
            self::AjustePositivo => 'Ajuste (+)',
            self::AjusteNegativo => 'Ajuste (−)',
            self::Baja => 'Baja',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Entrada => 'success',
            self::Salida => 'primary',
            self::AjustePositivo => 'info',
            self::AjusteNegativo => 'warning',
            self::Baja => 'dark',
        };
    }

    /**
     * Si el movimiento suma o resta al saldo de la referencia. Es lo que permite
     * verificar el invariante del ledger: cantidad_actual = Σ(suma) − Σ(resta).
     */
    public function suma(): bool
    {
        return match ($this) {
            self::Entrada, self::AjustePositivo => true,
            self::Salida, self::AjusteNegativo, self::Baja => false,
        };
    }
}
