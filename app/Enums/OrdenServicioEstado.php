<?php

namespace App\Enums;

enum OrdenServicioEstado: string
{
    case Activa = 'activa';
    case EnEjecucion = 'en_ejecucion';
    case Completada = 'completada';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Activa => 'Activa',
            self::EnEjecucion => 'En Ejecución',
            self::Completada => 'Completada',
            self::Cancelada => 'Cancelada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Activa => 'info',
            self::EnEjecucion => 'primary',
            self::Completada => 'success',
            self::Cancelada => 'danger',
        };
    }
}