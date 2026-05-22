<?php

namespace App\Enums;

enum SolicitudEstado: string
{
    case Pendiente = 'pendiente';
    case Asignada = 'asignada';
    case EnProceso = 'en_proceso';
    case Completada = 'completada';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Asignada => 'Asignada',
            self::EnProceso => 'En Proceso',
            self::Completada => 'Completada',
            self::Cancelada => 'Cancelada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pendiente => 'warning',
            self::Asignada => 'info',
            self::EnProceso => 'primary',
            self::Completada => 'success',
            self::Cancelada => 'danger',
        };
    }
}