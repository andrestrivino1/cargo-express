<?php

namespace App\Enums;

/**
 * Tipo de contenedor, según la nomenclatura estándar de la industria.
 */
enum TipoContenedor: string
{
    case Dry = 'dry';
    case Reefer = 'reefer';
    case OpenTop = 'open_top';
    case FlatRack = 'flat_rack';
    case Tank = 'tank';

    public function label(): string
    {
        return match ($this) {
            self::Dry => 'Dry (seco)',
            self::Reefer => 'Reefer (refrigerado)',
            self::OpenTop => 'Open Top',
            self::FlatRack => 'Flat Rack',
            self::Tank => 'Tank (cisterna)',
        };
    }

    /**
     * Opciones para poblar un <select>.
     *
     * @return array<string, string>
     */
    public static function opciones(): array
    {
        $opciones = [];

        foreach (self::cases() as $caso) {
            $opciones[$caso->value] = $caso->label();
        }

        return $opciones;
    }
}
