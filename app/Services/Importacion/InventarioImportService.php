<?php

namespace App\Services\Importacion;

use App\Enums\ImportRowEstado;
use App\Imports\InventarioHistoricoImport;
use App\Models\ImportBatch;
use App\Notifications\ImportacionFinalizada;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Orquestador de la importación.
 *
 * - Modo `validar` (dry-run): no escribe en tablas operativas.
 * - Modo `importar`: persiste vía ContenedorResolver + ReferenciaMapper +
 *   HistorialDespachoMapper, dentro de una DB::transaction() por hoja.
 *
 * Antes de procesar, hace un pre-pass para detectar el mismo número de
 * contenedor en hojas (clientes) distintos — esos casos se marcan como
 * CONTENEDOR_CONFLICTO_CLIENTE y no se importan.
 */
final class InventarioImportService
{
    private ?ClienteResolver $clienteResolverActivo = null;

    private ?ContenedorResolver $contenedorResolverActivo = null;

    private ?UbicacionPatioPersister $ubicacionPersisterActivo = null;

    private ?ReferenciaMapper $referenciaMapper = null;

    private ?HistorialDespachoMapper $historialMapper = null;

    /** @var Collection<string, true> Contenedores ya creados/actualizados, key = numero|cliente_id */
    private Collection $contenedoresProcesados;

    /** @var Collection<string, string> Conflictos detectados: numero => 'CLIENTE A vs CLIENTE B' */
    private Collection $conflictosContenedor;

    public function __construct(
        private readonly ExcelHeaderResolver $resolver,
        private readonly UbicacionResolver $ubicaciones,
        private readonly ImportReportBuilder $reporte,
        private readonly PendingFieldsRegistrar $pendingRegistrar,
    ) {
        $this->contenedoresProcesados = collect();
        $this->conflictosContenedor = collect();
    }

    public function procesar(ImportBatch $batch): void
    {
        $this->clienteResolverActivo = new ClienteResolver(modoCacheMemoria: $batch->dry_run);

        if (! $batch->dry_run) {
            $this->ubicacionPersisterActivo = new UbicacionPatioPersister;
            $this->contenedorResolverActivo = new ContenedorResolver($this->pendingRegistrar);
            $this->referenciaMapper = new ReferenciaMapper(new ProductoResolver);
            $this->historialMapper = new HistorialDespachoMapper($this->pendingRegistrar);
        }

        // Pre-pass: detectar conflictos contenedor↔cliente entre hojas.
        if (! $batch->dry_run) {
            $this->detectarConflictos($batch);
        }

        $validator = new RowValidator(
            $this->ubicaciones,
            $batch->fecha_corte !== null ? \Carbon\CarbonImmutable::instance($batch->fecha_corte) : null,
        );

        $import = new InventarioHistoricoImport(
            service: $this,
            batch: $batch,
            resolver: $this->resolver,
            validator: $validator,
            reporte: $this->reporte,
        );

        Excel::import($import, $batch->archivo_path, config('importacion.disco'));

        $this->reporte->consolidar($batch, $this->clienteResolverActivo);

        if (! $batch->dry_run) {
            $this->actualizarContadoresPersistencia($batch);
        }

        $batch->refresh();
        $this->notificarSeguro($batch);
    }

    /** La notificación es accesoria: si falla (canal mal configurado, tabla ausente, etc.)
     *  no debe contaminar el estado final del batch. */
    private function notificarSeguro(ImportBatch $batch): void
    {
        try {
            $batch->usuario?->notify(new ImportacionFinalizada($batch));
        } catch (\Throwable $e) {
            \Log::warning('Fallo enviando ImportacionFinalizada', [
                'batch_id' => $batch->id, 'error' => $e->getMessage(),
            ]);
        }
    }

    /** Llamado por ClienteSheetImport por cada fila ya validada. */
    public function procesarResultadoFila(
        ImportBatch $batch,
        string $hoja,
        int $filaExcel,
        array $payload,
        RowValidationResult $resultado,
    ): void {
        if ($resultado->estado === ImportRowEstado::Error) {
            $this->reporte->registrarFila(
                batch: $batch,
                hoja: $hoja,
                filaExcel: $filaExcel,
                estado: ImportRowEstado::Error,
                tipo: $resultado->tipoError,
                mensaje: $resultado->mensaje,
                payload: $payload,
            );

            return;
        }

        if ($resultado->estado === ImportRowEstado::Ignorado) {
            $this->reporte->registrarFila(
                batch: $batch,
                hoja: $hoja,
                filaExcel: $filaExcel,
                estado: ImportRowEstado::Ignorado,
                tipo: $resultado->tipoError,
                mensaje: $resultado->mensaje,
                payload: $payload,
            );

            return;
        }

        $datos = $resultado->datosNormalizados;
        $cliente = $this->clienteResolverActivo->resolver($datos['cliente'], $batch);

        // Conflicto contenedor↔cliente: importar igual con notas para que el operador resuelva.
        $notasConflicto = null;
        if ($datos['contenedor'] !== '' && $this->conflictosContenedor->has($datos['contenedor'])) {
            $notasConflicto = 'Mismo número de contenedor aparece en clientes distintos: '
                .$this->conflictosContenedor->get($datos['contenedor'])
                .'. Revisar y decidir el cliente correcto.';
        }

        // Duplicado: contenedor ya procesado en este batch con el MISMO cliente — sigue OK (varias referencias del mismo contenedor).
        // Duplicado pre-existente en BD: ContenedorResolver lo detecta y reusa; sin acción adicional.

        if ($batch->dry_run) {
            $this->reporte->registrarFila(
                batch: $batch,
                hoja: $hoja,
                filaExcel: $filaExcel,
                estado: ImportRowEstado::Importado,
                tipo: 'OK',
                mensaje: 'Fila importable',
                payload: $payload,
                userClienteId: $cliente->exists ? $cliente->getKey() : null,
            );
            $this->registrarAdvertencias($batch, $hoja, $filaExcel, $resultado);

            return;
        }

        // Persistencia real (dentro de la transacción de la hoja)
        $ubic = $this->ubicacionPersisterActivo->obtenerOCrear($datos['ubicacion']);
        $contenedor = $this->contenedorResolverActivo->obtenerOCrear(
            cliente: $cliente,
            numeroNormalizado: $datos['contenedor'],
            fechaIngreso: $datos['fecha_deposito'],
            batch: $batch,
            notasConflicto: $notasConflicto,
        );
        $referencia = $this->referenciaMapper->crear($contenedor, $cliente, $ubic, $datos, $batch);
        $tarjasCreadas = $this->historialMapper->crearHistorial(
            ref: $referencia,
            cliente: $cliente,
            pares: $datos['pares_despacho'],
            batch: $batch,
        );

        $this->contenedoresProcesados->put($datos['contenedor'].'|'.$cliente->getKey(), true);

        $this->reporte->registrarFila(
            batch: $batch,
            hoja: $hoja,
            filaExcel: $filaExcel,
            estado: ImportRowEstado::Importado,
            tipo: 'OK',
            mensaje: 'Fila importada',
            payload: $payload,
            userClienteId: $cliente->getKey(),
            contenedorId: $contenedor->getKey(),
            referenciaId: $referencia->getKey(),
        );

        $this->registrarAdvertencias($batch, $hoja, $filaExcel, $resultado);
    }

    /** Devuelve la lista ordenada de hojas existentes en el archivo del batch. */
    public function nombresDeHojas(ImportBatch $batch): array
    {
        $rutaAbsoluta = Storage::disk(config('importacion.disco'))->path($batch->archivo_path);
        $reader = IOFactory::createReaderForFile($rutaAbsoluta);
        $reader->setReadDataOnly(true);

        return $reader->listWorksheetNames($rutaAbsoluta);
    }

    private function registrarAdvertencias(ImportBatch $batch, string $hoja, int $filaExcel, RowValidationResult $r): void
    {
        foreach ($r->advertencias as $adv) {
            $this->reporte->registrarFila(
                batch: $batch,
                hoja: $hoja,
                filaExcel: $filaExcel,
                estado: ImportRowEstado::Advertencia,
                tipo: $adv['tipo'],
                mensaje: $adv['mensaje'],
                payload: null,
            );
        }
    }

    /**
     * Lee el archivo dos veces (la segunda en pre-pass) para detectar
     * (numero_contenedor, cliente_diferentes_hojas). Para el archivo real
     * (~20k filas) el costo es aceptable porque PhpSpreadsheet en modo
     * readDataOnly procesa todo en ~30 s.
     */
    private function detectarConflictos(ImportBatch $batch): void
    {
        $rutaAbsoluta = Storage::disk(config('importacion.disco'))->path($batch->archivo_path);
        $reader = IOFactory::createReaderForFile($rutaAbsoluta);
        $reader->setReadDataOnly(true);
        $wb = $reader->load($rutaAbsoluta);

        $contenedoresPorCliente = []; // numero => set(cliente)

        foreach ($wb->getSheetNames() as $nombreHoja) {
            if (mb_strtolower(trim($nombreHoja)) === 'hoja1' || str_starts_with(mb_strtolower(trim($nombreHoja)), 'copia de')) {
                continue;
            }
            $ws = $wb->getSheetByName($nombreHoja);
            $primeraFila = $ws->rangeToArray('A1:'.$ws->getHighestColumn().'1', null, true, false)[0] ?? [];
            $map = $this->resolver->resolve($primeraFila);
            if (! $map->tieneTodasLasColumnasRequeridas()) {
                continue;
            }
            $idxCliente = $map->indice('cliente');
            $idxCont = $map->indice('contenedor');

            $high = $ws->getHighestDataRow();
            for ($r = 2; $r <= $high; $r++) {
                $cliente = trim((string) $ws->getCellByColumnAndRow($idxCliente + 1, $r)->getValue());
                $contRaw = trim((string) $ws->getCellByColumnAndRow($idxCont + 1, $r)->getValue());
                if ($cliente === '' || $contRaw === '') {
                    continue;
                }
                $cont = mb_strtoupper(preg_replace('/\s+/u', '', $contRaw) ?? '');
                $contenedoresPorCliente[$cont][$cliente] = true;
            }
        }

        foreach ($contenedoresPorCliente as $cont => $setClientes) {
            if (count($setClientes) > 1) {
                $this->conflictosContenedor->put(
                    $cont,
                    implode(' vs ', array_keys($setClientes))
                );
            }
        }

        $wb->disconnectWorksheets();
        unset($wb);
    }

    private function actualizarContadoresPersistencia(ImportBatch $batch): void
    {
        $stats = DB::table('contenedores')->where('import_batch_id', $batch->getKey())->count();
        $refs = DB::table('referencias')->join('contenedores', 'referencias.contenedor_id', '=', 'contenedores.id')
            ->where('contenedores.import_batch_id', $batch->getKey())->count();
        $tarjas = DB::table('tarjas')->where('import_batch_id', $batch->getKey())->count();

        $batch->forceFill([
            'contenedores_creados' => $stats,
            'referencias_creadas' => $refs,
            'despachos_historicos_creados' => $tarjas,
        ])->save();
    }
}
