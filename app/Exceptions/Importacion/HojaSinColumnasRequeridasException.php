<?php

namespace App\Exceptions\Importacion;

use RuntimeException;

class HojaSinColumnasRequeridasException extends RuntimeException
{
    /** @param string[] $columnasFaltantes */
    public function __construct(
        public readonly string $hoja,
        public readonly array $columnasFaltantes,
    ) {
        parent::__construct(
            "La hoja '{$hoja}' no contiene las columnas requeridas: ".implode(', ', $columnasFaltantes)
        );
    }
}
