<?php

namespace App\Enums;

enum GateEventTipo: string
{
    case GateIn = 'gate_in';
    case GateOut = 'gate_out';

    public function label(): string
    {
        return match ($this) {
            self::GateIn => 'Ingreso',
            self::GateOut => 'Salida',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::GateIn => 'success',
            self::GateOut => 'info',
        };
    }
}