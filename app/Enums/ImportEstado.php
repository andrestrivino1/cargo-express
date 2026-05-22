<?php

namespace App\Enums;

enum ImportEstado: string
{
    case Pendiente = 'pendiente';
    case Procesando = 'procesando';
    case Completado = 'completado';
    case Fallido = 'fallido';
    case Cancelado = 'cancelado';

    public function esTerminal(): bool
    {
        return in_array($this, [self::Completado, self::Fallido, self::Cancelado], true);
    }
}
