<?php

namespace App\Enums;

/**
 * Estado de una cita de llegada.
 *
 * OJO: `Vencida` nunca se persiste en la columna `citas.estado`. Es el estado
 * que devuelve Cita::estadoEfectivo() cuando una cita sigue Programada y su
 * fecha esperada ya pasó. Se calcula al leer porque el hosting no tiene cron
 * que pudiera marcarlas, y así el estado nunca queda obsoleto.
 */
enum CitaEstado: string
{
    case Programada = 'programada';
    case Atendida = 'atendida';
    case Vencida = 'vencida';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Programada => 'Programada',
            self::Atendida => 'Atendida',
            self::Vencida => 'Vencida',
            self::Cancelada => 'Cancelada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Programada => 'info',
            self::Atendida => 'success',
            self::Vencida => 'warning',
            self::Cancelada => 'secondary',
        };
    }

    /**
     * Estados que sí se guardan en base de datos.
     *
     * @return array<int, string>
     */
    public static function persistibles(): array
    {
        return [
            self::Programada->value,
            self::Atendida->value,
            self::Cancelada->value,
        ];
    }
}
