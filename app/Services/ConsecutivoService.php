<?php

namespace App\Services;

use App\Models\Secuencia;
use Illuminate\Support\Facades\DB;

class ConsecutivoService
{
    /**
     * Devuelve el siguiente consecutivo para la clave dada de forma atómica.
     *
     * Usa un bloqueo de fila para evitar duplicados ante operaciones concurrentes.
     * La secuencia se crea en 0 si no existe (el primer consecutivo emitido será 1).
     */
    public function siguiente(string $clave): int
    {
        return DB::transaction(function () use ($clave) {
            $secuencia = Secuencia::query()
                ->where('clave', $clave)
                ->lockForUpdate()
                ->first();

            if (! $secuencia) {
                $secuencia = Secuencia::create(['clave' => $clave, 'valor' => 0]);
            }

            $secuencia->valor++;
            $secuencia->save();

            return $secuencia->valor;
        });
    }
}
