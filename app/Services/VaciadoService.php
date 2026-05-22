<?php

namespace App\Services;

use App\Enums\OrdenVaciadoEstado;
use App\Models\Novedad;
use App\Models\OrdenVaciado;
use App\Models\Referencia;
use App\Models\User;
use App\Notifications\NovedadRegistradaNotification;
use Illuminate\Support\Facades\DB;

class VaciadoService
{
    public function programar(array $data, User $supervisor): OrdenVaciado
    {
        $orden = OrdenVaciado::create([
            'contenedor_id' => $data['contenedor_id'],
            'supervisor_id' => $supervisor->id,
            'fecha_programada' => $data['fecha_programada'],
            'notas' => $data['notas'] ?? null,
            'estado' => OrdenVaciadoEstado::Programada,
        ]);

        if (!empty($data['fotos'])) {
            $orden->guardarFotos($data['fotos'], "vaciado/{$orden->id}/fotos");
        }

        return $orden;
    }

    public function iniciar(OrdenVaciado $orden): void
    {
        $orden->fecha_inicio = now();
        $orden->estado = OrdenVaciadoEstado::EnProceso;
        $orden->save();

        $orden->contenedor->marcarEnVaciado();
    }

    public function finalizar(OrdenVaciado $orden): void
    {
        $orden->fecha_fin = now();
        $orden->estado = OrdenVaciadoEstado::Completada;
        $orden->save();

        $orden->contenedor->marcarVaciadoCompletado();
    }

    public function registrarNovedad(OrdenVaciado $orden, array $data, User $operador): Novedad
    {
        return DB::transaction(function () use ($orden, $data, $operador) {
            $cantidadAfectada = isset($data['cantidad_afectada']) ? (int) $data['cantidad_afectada'] : null;

            $novedad = $orden->novedades()->create([
                'operador_id'       => $operador->id,
                'tipo'              => $data['tipo'],
                'descripcion'       => $data['descripcion'],
                'referencia_id'     => $data['referencia_id'] ?? null,
                'cantidad_afectada' => $cantidadAfectada,
            ]);

            // Descontar cantidad de la referencia si aplica
            if ($cantidadAfectada && !empty($data['referencia_id'])) {
                $referencia = Referencia::find($data['referencia_id']);
                if ($referencia) {
                    $referencia->cantidad_actual = max(0, $referencia->cantidad_actual - $cantidadAfectada);
                    $referencia->save();
                }
            }

            if (!empty($data['fotos'])) {
                $novedad->guardarFotos($data['fotos'], "novedades/{$novedad->id}");
            }

            // Notificar al cliente
            $cliente = $orden->contenedor->ordenServicio->solicitud->cliente ?? null;
            if ($cliente) {
                $cliente->notify(new NovedadRegistradaNotification($novedad));
            }

            return $novedad;
        });
    }
}