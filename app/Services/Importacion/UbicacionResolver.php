<?php

namespace App\Services\Importacion;

/**
 * Normaliza la notación heterogénea de ubicación:
 *   "Modulo6 Bloque B"      → modulo=6, posicion=B
 *   "Modulo 2 Bloque A"     → modulo=2, posicion=A
 *   "Modulo 3-Bloque C"     → modulo=3, posicion=C
 *   "Modulo6 Bloque B  "    → modulo=6, posicion=B
 * Si no matchea, devuelve {modulo: <texto original>, posicion: 'S/N', normalizada: false}.
 */
final class UbicacionResolver
{
    private const REGEX = '/m[oó]dulo\s*([\w-]+)[\s\-]+bloque\s*([\w-]+)/iu';

    /** @return array{modulo:string, posicion:string, normalizada:bool} */
    public function resolverONormalizar(?string $raw): array
    {
        $valor = trim((string) ($raw ?? ''));
        if ($valor === '') {
            return ['modulo' => 'SIN_UBICACION', 'posicion' => 'S/N', 'normalizada' => false];
        }

        if (preg_match(self::REGEX, $valor, $matches) === 1) {
            return [
                'modulo' => trim($matches[1]),
                'posicion' => trim($matches[2]),
                'normalizada' => true,
            ];
        }

        return [
            'modulo' => $valor,
            'posicion' => 'S/N',
            'normalizada' => false,
        ];
    }
}
