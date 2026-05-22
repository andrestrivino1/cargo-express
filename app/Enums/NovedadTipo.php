<?php

namespace App\Enums;

enum NovedadTipo: string
{
    case Averia = 'averia';
    case Faltante = 'faltante';
    case DanoVisible = 'dano_visible';

    public function label(): string
    {
        return match ($this) {
            self::Averia => 'Avería',
            self::Faltante => 'Faltante',
            self::DanoVisible => 'Daño Visible',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Averia => 'danger',
            self::Faltante => 'warning',
            self::DanoVisible => 'info',
        };
    }
}