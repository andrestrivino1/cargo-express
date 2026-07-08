<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Se lanza cuando un intento de registro de salida (idempotency_key) ya fue
 * procesado. Transporta el id de la tarja existente para poder redirigir a la
 * Orden de Salida ya creada en vez de crear un duplicado.
 */
class SalidaDuplicadaException extends RuntimeException
{
    public function __construct(private readonly int $tarjaId)
    {
        parent::__construct('El despacho ya estaba registrado.');
    }

    public function tarjaId(): int
    {
        return $this->tarjaId;
    }
}
