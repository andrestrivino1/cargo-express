<?php

namespace App\Support;

/**
 * Normalización de identificadores digitados a mano (placa, cédula, número de
 * contenedor).
 *
 * Los datos de una cita se digitan desde un teléfono, con espacios, guiones y
 * mayúsculas inconsistentes: "ABC-123", "abc 123", "ABC123". Si se guardaran tal
 * cual, la búsqueda del portero en la puerta fallaría.
 *
 * Se normaliza AL ESCRIBIR (no al buscar) para que la consulta pueda usar el
 * índice de la columna en vez de una función sobre ella.
 */
class Normalizador
{
    /**
     * Mayúsculas, sin espacios ni separadores. Devuelve null si queda vacío.
     */
    public static function identificador(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $limpio = preg_replace('/[^A-Za-z0-9]/', '', $valor) ?? '';

        return $limpio === '' ? null : mb_strtoupper($limpio);
    }
}
