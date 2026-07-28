<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Se lanza cuando un intento de registro de ingreso (idempotency_key) ya fue
 * procesado. Transporta el id del ingreso existente para poder redirigir al que
 * ya se creó en vez de generar un duplicado.
 */
class IngresoDuplicadoException extends RuntimeException
{
    public function __construct(private readonly int $ingresoId)
    {
        parent::__construct('El ingreso ya estaba registrado.');
    }

    public function ingresoId(): int
    {
        return $this->ingresoId;
    }
}
