<?php

namespace App\Enums;

/**
 * Tamaño del contenedor en pies.
 */
enum TamanoContenedor: string
{
    case Veinte = '20';
    case Cuarenta = '40';
    case CuarentaHC = '40hc';
    case CuarentaYCinco = '45';

    public function label(): string
    {
        return match ($this) {
            self::Veinte => "20'",
            self::Cuarenta => "40'",
            self::CuarentaHC => "40' High Cube",
            self::CuarentaYCinco => "45'",
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
