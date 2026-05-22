<?php

namespace App\Imports\Sheets;

use App\Models\ImportBatch;
use App\Services\Importacion\ExcelHeaderResolver;
use App\Services\Importacion\HeaderMap;
use App\Services\Importacion\ImportReportBuilder;
use App\Services\Importacion\InventarioImportService;
use App\Services\Importacion\RowValidator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

/**
 * Procesa una hoja del Excel — una por cliente. Resuelve el header de la
 * primera fila, valida cada fila subsiguiente y delega al orquestador.
 *
 * Nota: WithChunkReading se removió porque interactúa mal con WithMultipleSheets
 * (solo invoca collection() con el primer chunk). Para hojas de ~1000 filas
 * × 30 columnas el consumo de memoria es aceptable (~30 MB por hoja máximo).
 */
class ClienteSheetImport implements ToCollection, WithCalculatedFormulas
{
    use Importable;

    private ?HeaderMap $headers = null;

    private bool $hojaInvalida = false;

    public function __construct(
        private readonly InventarioImportService $service,
        private readonly ImportBatch $batch,
        private readonly string $nombreHoja,
        private readonly ExcelHeaderResolver $resolver,
        private readonly RowValidator $validator,
        private readonly ImportReportBuilder $reporte,
    ) {}

    public function collection(Collection $rows): void
    {
        if ($this->hojaInvalida || $rows->isEmpty()) {
            return;
        }

        $primera = $rows->shift();
        $this->headers = $this->resolver->resolve($this->normalizarFila($primera));

        if (! $this->headers->tieneTodasLasColumnasRequeridas()) {
            // Marcamos la hoja como ignorada pero continuamos con las demás hojas
            // del archivo (FR-011: el reporte debe clasificar el 100% de las filas).
            $this->reporte->registrarHojaIgnorada(
                $this->batch,
                $this->nombreHoja,
                'HOJA_SIN_COLUMNAS_REQUERIDAS',
                'Faltan columnas obligatorias: '.implode(', ', $this->headers->columnasFaltantes),
            );
            $this->hojaInvalida = true;

            return;
        }

        $procesar = function () use ($rows) {
            foreach ($rows as $i => $fila) {
                $filaExcel = $i + 2; // +2 porque shift quitó el header (fila 1) y los índices son 0-based
                $arr = $this->normalizarFila($fila);

                if ($this->esFilaCompletamenteVacia($arr) || $this->esFilaSinDatosEsenciales($arr)) {
                    continue;
                }

                $resultado = $this->validator->validar($arr, $this->headers);
                $this->service->procesarResultadoFila($this->batch, $this->nombreHoja, $filaExcel, $arr, $resultado);
            }
        };

        // En modo importar: transacción por hoja (research.md §R1 — granularidad correcta
        // ahora que la hoja completa se procesa en una sola llamada a collection()).
        $this->batch->dry_run ? $procesar() : DB::transaction($procesar);
    }

    /** @return array<int, mixed> */
    private function normalizarFila(mixed $fila): array
    {
        if ($fila instanceof Collection) {
            return $fila->values()->all();
        }

        return is_array($fila) ? array_values($fila) : [(string) $fila];
    }

    /** @param array<int, mixed> $fila */
    private function esFilaCompletamenteVacia(array $fila): bool
    {
        foreach ($fila as $valor) {
            if (trim((string) ($valor ?? '')) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Filas "informativas" del Excel (separadores, totales, observaciones sueltas)
     * donde ninguno de los 5 campos clave está presente. Se omiten silenciosamente
     * en lugar de marcarlas como error — no son filas de datos reales.
     *
     * @param  array<int, mixed>  $fila
     */
    private function esFilaSinDatosEsenciales(array $fila): bool
    {
        $essential = ['cliente', 'contenedor', 'fecha_deposito', 'unidad', 'inventario_fisico'];
        foreach ($essential as $col) {
            $idx = $this->headers->indice($col);
            if ($idx !== null && trim((string) ($fila[$idx] ?? '')) !== '') {
                return false;
            }
        }

        return true;
    }
}
