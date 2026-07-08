<?php

namespace App\Services;

use App\Models\IdempotencyKey;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Barrera de idempotencia basada en un índice UNIQUE sobre el token.
 *
 * El uso previsto es "insert-first" dentro de la misma transacción que crea el
 * recurso: si {@see reservar()} devuelve false, el intento ya fue procesado y
 * se debe redirigir al recurso existente; si devuelve true, se procede a crear
 * el recurso y luego {@see asociarRecurso()} lo enlaza.
 */
class IdempotencyService
{
    /**
     * Reserva el token de forma atómica.
     *
     * @return bool true si el token se reservó por primera vez; false si ya existía.
     */
    public function reservar(string $token, string $scope, int $usuarioId): bool
    {
        try {
            IdempotencyKey::create([
                'token' => $token,
                'scope' => $scope,
                'usuario_id' => $usuarioId,
            ]);

            return true;
        } catch (UniqueConstraintViolationException) {
            return false;
        }
    }

    /**
     * Enlaza el token ya reservado con el id del recurso creado.
     */
    public function asociarRecurso(string $token, int $resourceId): void
    {
        IdempotencyKey::query()
            ->where('token', $token)
            ->update(['resource_id' => $resourceId]);
    }

    /**
     * Devuelve el id del recurso asociado a un token ya reservado, o null.
     *
     * Usa un bloqueo de lectura para garantizar visibilidad del valor confirmado
     * por la petición que ganó la carrera.
     */
    public function recursoReservado(string $token, string $scope): ?int
    {
        $id = IdempotencyKey::query()
            ->where('token', $token)
            ->where('scope', $scope)
            ->lockForUpdate()
            ->value('resource_id');

        return $id !== null ? (int) $id : null;
    }
}
