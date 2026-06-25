<?php

namespace App\Enums;

enum MovimientoTipo: string
{
    case Entrada = 'entrada';
    case Salida = 'salida';

    public function label(): string
    {
        return match ($this) {
            self::Entrada => 'Entrada',
            self::Salida => 'Salida',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Entrada => 'success',
            self::Salida => 'primary',
        };
    }
}
