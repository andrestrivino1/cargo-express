<?php

namespace App\Enums;

enum OrdenVaciadoEstado: string
{
    case Programada = 'programada';
    case EnProceso = 'en_proceso';
    case Completada = 'completada';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Programada => 'Programada',
            self::EnProceso => 'En Proceso',
            self::Completada => 'Completada',
            self::Cancelada => 'Cancelada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Programada => 'info',
            self::EnProceso => 'primary',
            self::Completada => 'success',
            self::Cancelada => 'danger',
        };
    }
}