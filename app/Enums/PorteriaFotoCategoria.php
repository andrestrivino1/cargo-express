<?php

namespace App\Enums;

/**
 * Las cuatro evidencias fotográficas obligatorias que el portero captura al
 * confirmar la llegada de un vehículo con cita. Se guardan en la tabla `photos`
 * (polimórfica) usando este valor en la columna `categoria`.
 */
enum PorteriaFotoCategoria: string
{
    case Vehiculo = 'vehiculo';
    case Contenedor = 'contenedor';
    case Sello = 'sello';
    case Tiquete = 'tiquete';

    public function label(): string
    {
        return match ($this) {
            self::Vehiculo => 'Foto del vehículo',
            self::Contenedor => 'Foto del contenedor',
            self::Sello => 'Foto del sello',
            self::Tiquete => 'Foto del tiquete',
        };
    }

    /**
     * Nombre del campo del formulario que transporta esta evidencia.
     */
    public function campo(): string
    {
        return 'foto_'.$this->value;
    }

    public function icono(): string
    {
        return match ($this) {
            self::Vehiculo => 'bi-truck',
            self::Contenedor => 'bi-box-seam',
            self::Sello => 'bi-shield-lock',
            self::Tiquete => 'bi-receipt',
        };
    }
}
