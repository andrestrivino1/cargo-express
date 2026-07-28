<?php

namespace App\Services;

use App\Enums\CitaEstado;
use App\Models\Cita;
use App\Models\Contenedor;
use App\Models\User;
use App\Support\Normalizador;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class CitaService
{
    /**
     * Agenda la llegada de un contenedor perteneciente a un ingreso ya registrado.
     *
     * @param  array<string, mixed>  $data
     */
    public function crear(array $data, User $usuario): Cita
    {
        $contenedor = Contenedor::findOrFail($data['contenedor_id']);

        return Cita::create($this->atributos($data, $contenedor) + [
            'estado' => CitaEstado::Programada,
            'creado_por' => $usuario->id,
        ]);
    }

    /**
     * Actualiza una cita. Una cita atendida es terminal; una vencida sí se puede
     * editar, porque cambiarle la fecha es reprogramarla.
     *
     * @param  array<string, mixed>  $data
     */
    public function actualizar(Cita $cita, array $data, User $usuario): Cita
    {
        $this->garantizarEditable($cita);

        $contenedor = Contenedor::findOrFail($data['contenedor_id']);

        $cita->update($this->atributos($data, $contenedor) + [
            'actualizado_por' => $usuario->id,
        ]);

        return $cita;
    }

    public function cancelar(Cita $cita, User $usuario): void
    {
        $this->garantizarEditable($cita);

        $cita->update([
            'estado' => CitaEstado::Cancelada,
            'actualizado_por' => $usuario->id,
        ]);
    }

    /**
     * ¿Este contenedor ya tiene una cita programada? Se advierte sin bloquear:
     * un contenedor puede volver legítimamente (sale vacío y regresa).
     */
    public function tieneCitaProgramada(int $contenedorId, ?int $excluyendoCitaId = null): bool
    {
        return Cita::where('contenedor_id', $contenedorId)
            ->where('estado', CitaEstado::Programada)
            ->when($excluyendoCitaId, fn ($q) => $q->whereKeyNot($excluyendoCitaId))
            ->exists();
    }

    /**
     * Listado paginado con filtros.
     *
     * @param  array<string, mixed>  $filtros
     */
    public function listar(array $filtros): LengthAwarePaginator
    {
        return Cita::query()
            ->with(['ingreso', 'contenedor', 'creador', 'registroPorteria'])
            ->when($filtros['fecha_esperada'] ?? null, fn ($q, $f) => $q->whereDate('fecha_esperada', $f))
            ->when($filtros['bl'] ?? null, fn ($q, $bl) => $q->whereHas('ingreso', fn ($i) => $i->where('bl', 'like', "%{$bl}%")))
            ->when($filtros['numero_contenedor'] ?? null, fn ($q, $n) => $q->where('numero_contenedor', 'like', '%'.Normalizador::identificador($n).'%'))
            ->when($filtros['placa'] ?? null, fn ($q, $p) => $q->where('placa', 'like', '%'.Normalizador::identificador($p).'%'))
            ->when($filtros['estado'] ?? null, fn ($q, $e) => $this->filtrarPorEstado($q, $e))
            ->orderByDesc('fecha_esperada')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * `vencida` no existe como valor en la columna: se traduce a la condición
     * que la define (programada + fecha pasada).
     */
    private function filtrarPorEstado($query, string $estado)
    {
        if ($estado === CitaEstado::Vencida->value) {
            return $query->vencidas();
        }

        if ($estado === CitaEstado::Programada->value) {
            return $query->where('estado', CitaEstado::Programada)
                ->whereDate('fecha_esperada', '>=', today());
        }

        return $query->where('estado', $estado);
    }

    /**
     * Atributos comunes al alta y a la edición. La placa y la cédula se guardan
     * normalizadas (para que la búsqueda del portero use el índice) y también tal
     * como se digitaron (para mostrarlas).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function atributos(array $data, Contenedor $contenedor): array
    {
        return [
            'ingreso_id' => $data['ingreso_id'],
            'contenedor_id' => $contenedor->id,
            'numero_contenedor' => Normalizador::identificador($contenedor->numero) ?? '',
            'tipo' => $data['tipo'],
            'tamano' => $data['tamano'],
            'condicion' => $data['condicion'],
            'fecha_esperada' => $data['fecha_esperada'],
            'conductor_nombre' => trim($data['conductor_nombre']),
            'conductor_cedula' => Normalizador::identificador($data['conductor_cedula']) ?? '',
            'conductor_cedula_original' => trim($data['conductor_cedula']),
            'placa' => Normalizador::identificador($data['placa']) ?? '',
            'placa_original' => trim($data['placa']),
            'empresa' => trim($data['empresa']),
        ];
    }

    private function garantizarEditable(Cita $cita): void
    {
        if (! $cita->puedeEditarse()) {
            throw ValidationException::withMessages([
                'estado' => "No se puede modificar una cita en estado {$cita->estadoEfectivo()->label()}.",
            ]);
        }
    }
}
