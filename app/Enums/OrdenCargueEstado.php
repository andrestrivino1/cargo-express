<?php

namespace App\Enums;

enum OrdenCargueEstado: string
{
    case Pendiente = 'pendiente';
    case Programada = 'programada';
    case EnProceso = 'en_proceso';
    case Completada = 'completada';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Programada => 'Programada',
            self::EnProceso => 'En Proceso',
            self::Completada => 'Completada',
            self::Cancelada => 'Cancelada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pendiente => 'warning',
            self::Programada => 'info',
            self::EnProceso => 'primary',
            self::Completada => 'success',
            self::Cancelada => 'danger',
        };
    }
}