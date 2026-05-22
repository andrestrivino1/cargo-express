<?php

namespace App\Services\Importacion;

use App\Enums\ContenedorEstado;
use App\Enums\OrdenServicioEstado;
use App\Enums\SolicitudEstado;
use App\Models\Contenedor;
use App\Models\ImportBatch;
use App\Models\OrdenServicio;
use App\Models\Solicitud;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Crea contenedores históricos junto con sus padres sintéticos
 * (Solicitud + OrdenServicio) y registra los campos PENDIENTE_HISTORICO.
 *
 * Caché en memoria para idempotencia dentro del mismo batch:
 * mismo (numero, cliente_id) ⇒ devuelve el contenedor ya creado.
 */
final class ContenedorResolver
{
    /** Placeholder que rellena campos NOT NULL del modelo hasta que el usuario los complete. */
    public const PLACEHOLDER = 'PENDIENTE_HISTORICO';

    /** @var Collection<string, Contenedor> */
    private Collection $cache;

    private int $seqPlaceholder = 0;

    public function __construct(
        private readonly PendingFieldsRegistrar $pending,
    ) {
        $this->cache = collect();
    }

    public function obtenerOCrear(
        User $cliente,
        string $numeroNormalizado,
        CarbonImmutable $fechaIngreso,
        ImportBatch $batch,
        ?string $notasConflicto = null,
    ): Contenedor {
        // Si llega vacío, genera placeholder único por batch+secuencia.
        // El operador edita el número real desde /pendientes.
        $esPlaceholder = $numeroNormalizado === '';
        if ($esPlaceholder) {
            $numeroNormalizado = 'PEND-'.$batch->getKey().'-'.str_pad((string) (++$this->seqPlaceholder), 4, '0', STR_PAD_LEFT);
        }

        $clave = $cliente->getKey().'|'.$numeroNormalizado;

        $cached = $this->cache->get($clave);
        if ($cached !== null) {
            return $cached;
        }

        // ¿Ya existe en BD con el mismo lote? (reimport con política omitir)
        $existente = Contenedor::query()
            ->where('numero', $numeroNormalizado)
            ->whereHas('ordenServicio.solicitud', fn ($q) => $q->where('cliente_id', $cliente->getKey()))
            ->first();

        if ($existente !== null) {
            $this->cache->put($clave, $existente);

            return $existente;
        }

        $solicitud = Solicitud::create([
            'cliente_id' => $cliente->getKey(),
            'numero_contenedor' => $numeroNormalizado,
            'estado' => SolicitudEstado::Completada,
            'fecha_solicitud' => $fechaIngreso,
            'import_batch_id' => $batch->getKey(),
        ]);
        $this->pending->registrar($solicitud, ['naviera', 'puerto_origen', 'descripcion'], $batch, prioridad: 30);

        $coordinadorId = $this->coordinadorPredeterminadoId($batch);
        $ordenServicio = OrdenServicio::create([
            'solicitud_id' => $solicitud->getKey(),
            'coordinador_id' => $coordinadorId,
            'vehiculo' => self::PLACEHOLDER,
            'conductor' => self::PLACEHOLDER,
            'conductor_documento' => null,
            'cita_puerto' => $fechaIngreso,
            'estado' => OrdenServicioEstado::Completada,
            'import_batch_id' => $batch->getKey(),
        ]);
        $this->pending->registrar(
            $ordenServicio,
            ['vehiculo', 'conductor', 'conductor_documento', 'cita_puerto'],
            $batch,
            prioridad: 60,
        );

        $contenedor = Contenedor::create([
            'orden_servicio_id' => $ordenServicio->getKey(),
            'numero' => $numeroNormalizado,
            'placa_vehiculo' => self::PLACEHOLDER,
            'tipo' => null,
            'estado' => ContenedorEstado::VaciadoCompletado,
            'fecha_ingreso' => $fechaIngreso,
            'fecha_salida' => null,
            'limpieza_registrada' => false,
            'destino_salida' => null,
            'notas_conflicto' => $notasConflicto,
            'import_batch_id' => $batch->getKey(),
        ]);

        // Catálogo de pendientes: campos básicos + numero si es placeholder
        // + notas_conflicto si hay conflicto con otro cliente.
        $camposPendientes = ['placa_vehiculo', 'tipo', 'destino_salida'];
        if ($esPlaceholder) {
            $camposPendientes[] = 'numero';
        }
        if ($notasConflicto !== null) {
            $camposPendientes[] = 'notas_conflicto';
        }

        $this->pending->registrar(
            $contenedor,
            $camposPendientes,
            $batch,
            // Prioridad alta si tiene placeholder o conflicto (necesita resolución urgente).
            prioridad: ($esPlaceholder || $notasConflicto !== null) ? 90 : 70,
        );

        $this->cache->put($clave, $contenedor);

        return $contenedor;
    }

    private function coordinadorPredeterminadoId(ImportBatch $batch): int
    {
        // El usuario que dispara la importación funge como coordinador en los padres sintéticos.
        return $batch->usuario_id;
    }
}
