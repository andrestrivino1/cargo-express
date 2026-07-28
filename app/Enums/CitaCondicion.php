<?php

namespace App\Enums;

/**
 * Condición del contenedor que llega: cargado o vacío.
 */
enum CitaCondicion: string
{
    case Full = 'full';
    case Vacio = 'vacio';

    public function label(): string
    {
        return match ($this) {
            self::Full => 'Full',
            self::Vacio => 'Vacío',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Full => 'primary',
            self::Vacio => 'secondary',
        };
    }
}
