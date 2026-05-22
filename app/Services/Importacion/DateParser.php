<?php

namespace App\Services\Importacion;

use Carbon\CarbonImmutable;
use Exception;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Parser tolerante de fechas heterogéneas del Excel origen (research.md §R5).
 * Convención colombiana: día primero.
 */
final class DateParser
{
    private const FORMATOS = ['d/m/Y', 'd/m/y', 'd-m-Y', 'd-m-y', 'Y-m-d'];

    /** Rango aceptado para descartar errores de captura tipo "23/4/206" (año 206 d.C.). */
    private const ANIO_MIN = 2000;

    private const ANIO_MAX = 2100;

    public static function parse(string|int|float|null $value): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value) && ! is_string($value)) {
            return self::desdeSerialExcel((float) $value);
        }

        $texto = trim((string) $value);
        if ($texto === '') {
            return null;
        }

        if (ctype_digit($texto) && (int) $texto > 100) {
            $desdeSerial = self::desdeSerialExcel((float) $texto);
            if ($desdeSerial !== null) {
                return $desdeSerial;
            }
        }

        foreach (self::FORMATOS as $formato) {
            try {
                $dt = CarbonImmutable::createFromFormat($formato, $texto);
                if ($dt !== false && self::anioRazonable($dt)) {
                    return $dt->startOfDay();
                }
            } catch (Exception) {
                continue;
            }
        }

        return null;
    }

    private static function anioRazonable(CarbonImmutable $dt): bool
    {
        return $dt->year >= self::ANIO_MIN && $dt->year <= self::ANIO_MAX;
    }

    private static function desdeSerialExcel(float $serial): ?CarbonImmutable
    {
        try {
            $dt = ExcelDate::excelToDateTimeObject($serial);
            $carbon = CarbonImmutable::instance($dt)->startOfDay();

            return self::anioRazonable($carbon) ? $carbon : null;
        } catch (Exception) {
            return null;
        }
    }
}
