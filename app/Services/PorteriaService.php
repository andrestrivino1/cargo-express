<?php

namespace App\Services;

use App\Enums\CitaEstado;
use App\Enums\PorteriaFotoCategoria;
use App\Models\Cita;
use App\Models\PorteriaNovedad;
use App\Models\PorteriaRegistro;
use App\Models\User;
use App\Support\Normalizador;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PorteriaService
{
    /**
     * Citas cuya fecha esperada es HOY: las accionables, sobre las que el portero
     * puede confirmar una llegada.
     *
     * @return Collection<int, Cita>
     */
    public function citasDeHoy(?string $busqueda = null): Collection
    {
        return $this->consulta($busqueda)
            ->delDia()
            ->orderBy('estado')
            ->orderBy('numero_contenedor')
            ->get();
    }

    /**
     * Citas agendadas para días posteriores a hoy. Son solo de seguimiento: le
     * permiten al portero saber qué vehículos se esperan, pero no se pueden
     * confirmar hasta que llegue su fecha. Un vehículo que se presenta antes de
     * tiempo es una reprogramación, y eso lo resuelve el rol `citas`.
     *
     * @return Collection<int, Cita>
     */
    public function citasProximas(?string $busqueda = null): Collection
    {
        return $this->consulta($busqueda)
            ->whereDate('fecha_esperada', '>', today())
            ->orderBy('fecha_esperada')
            ->orderBy('numero_contenedor')
            ->get();
    }

    /**
     * Base común: citas vivas (ni canceladas) con sus relaciones y el filtro de
     * búsqueda ya aplicado.
     */
    private function consulta(?string $busqueda): Builder
    {
        return Cita::query()
            ->whereIn('estado', [CitaEstado::Programada, CitaEstado::Atendida])
            ->buscar($busqueda)
            ->with(['ingreso', 'contenedor', 'registroPorteria']);
    }

    /**
     * Confirma la llegada de un vehículo con cita.
     *
     * Todo ocurre en una sola transacción: o queda el registro con sus cuatro
     * evidencias y la cita atendida, o no queda nada. Un fallo a mitad de la
     * subida no puede dejar la cita medio confirmada ni fotos huérfanas.
     *
     * @param  array<string, UploadedFile>  $fotos  indexado por valor de PorteriaFotoCategoria
     */
    public function confirmarLlegada(Cita $cita, array $fotos, ?string $observaciones, User $portero): PorteriaRegistro
    {
        $this->garantizarConfirmable($cita);

        return DB::transaction(function () use ($cita, $fotos, $observaciones, $portero) {
            $registro = PorteriaRegistro::create([
                'cita_id' => $cita->id,
                'portero_id' => $portero->id,
                'llegada_at' => now(),
                'observaciones' => $observaciones,
            ]);

            foreach (PorteriaFotoCategoria::cases() as $categoria) {
                $registro->guardarArchivo(
                    $fotos[$categoria->value],
                    "porteria/{$registro->id}",
                    'foto',
                    $categoria->value,
                );
            }

            $cita->update(['estado' => CitaEstado::Atendida]);

            return $registro;
        });
    }

    /**
     * Constancia de un vehículo que se presentó sin cita para hoy.
     * No crea ni modifica ninguna cita: agendar corresponde al rol `citas`.
     *
     * @param  array<string, mixed>  $data
     */
    public function registrarNovedad(array $data, User $portero): PorteriaNovedad
    {
        return PorteriaNovedad::create([
            'portero_id' => $portero->id,
            'placa' => Normalizador::identificador($data['placa'] ?? null),
            'numero_contenedor' => Normalizador::identificador($data['numero_contenedor'] ?? null),
            'descripcion' => trim($data['descripcion']),
            'reportado_at' => now(),
        ]);
    }

    /**
     * Una cita solo se confirma si es de HOY y sigue programada. La restricción
     * UNIQUE sobre porteria_registros.cita_id es la red de seguridad en base de
     * datos ante envíos concurrentes.
     */
    private function garantizarConfirmable(Cita $cita): void
    {
        if ($cita->estaAtendida()) {
            throw ValidationException::withMessages([
                'cita' => 'Esta cita ya fue confirmada en portería.',
            ]);
        }

        if ($cita->estado !== CitaEstado::Programada) {
            throw ValidationException::withMessages([
                'cita' => "No se puede confirmar la llegada de una cita {$cita->estadoEfectivo()->label()}.",
            ]);
        }

        // Las citas futuras son visibles para seguimiento, pero no confirmables:
        // un vehículo que se adelanta requiere reprogramar la cita.
        if (! $cita->esDeHoy()) {
            throw ValidationException::withMessages([
                'cita' => "Esta cita está agendada para el {$cita->fecha_esperada->format('d/m/Y')}. "
                    .'Si el vehículo llegó antes, pide al área de citas que la reprograme.',
            ]);
        }
    }
}
