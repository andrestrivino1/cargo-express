<?php

namespace App\Services\Importacion;

use App\Enums\ImportEstado;
use App\Enums\ImportRowEstado;
use App\Models\ImportBatch;
use App\Models\ImportRowResult;
use Illuminate\Support\Facades\DB;

/**
 * Acumula resultados por fila y al final consolida los contadores
 * en la cabecera del batch. Centraliza la escritura de ImportRowResult
 * para mantener la lógica de auditoría en un solo lugar (DRY).
 */
final class ImportReportBuilder
{
    /** @var array<string, int> */
    private array $conteoPorHoja = [];

    /** @var array<string, int> */
    private array $clientesAResolver = [];

    public function registrarFila(
        ImportBatch $batch,
        string $hoja,
        int $filaExcel,
        ImportRowEstado $estado,
        ?string $tipo,
        string $mensaje,
        ?array $payload = null,
        ?int $userClienteId = null,
        ?int $contenedorId = null,
        ?int $referenciaId = null,
    ): void {
        ImportRowResult::create([
            'import_batch_id' => $batch->getKey(),
            'hoja' => $hoja,
            'fila_excel' => $filaExcel,
            'estado' => $estado,
            'tipo' => $tipo,
            'mensaje' => $mensaje,
            'payload_original' => $payload,
            'user_cliente_id' => $userClienteId,
            'contenedor_id' => $contenedorId,
            'referencia_id' => $referenciaId,
        ]);

        $key = $hoja.'|'.$estado->value;
        $this->conteoPorHoja[$key] = ($this->conteoPorHoja[$key] ?? 0) + 1;
    }

    public function registrarHojaIgnorada(ImportBatch $batch, string $hoja, string $tipo, string $motivo): void
    {
        $this->registrarFila(
            $batch,
            $hoja,
            0,
            ImportRowEstado::Ignorado,
            $tipo,
            $motivo,
        );
    }

    public function registrarClienteAResolver(string $nombre): void
    {
        $this->clientesAResolver[$nombre] = ($this->clientesAResolver[$nombre] ?? 0) + 1;
    }

    public function consolidar(ImportBatch $batch, ClienteResolver $clienteResolver): void
    {
        $stats = ImportRowResult::query()
            ->where('import_batch_id', $batch->getKey())
            ->selectRaw('estado, COUNT(*) as c')
            ->groupBy('estado')
            ->pluck('c', 'estado');

        $batch->forceFill([
            'total_filas' => (int) DB::table('import_row_results')->where('import_batch_id', $batch->getKey())->count(),
            'importables' => (int) ($stats[ImportRowEstado::Importado->value] ?? 0),
            'errores' => (int) ($stats[ImportRowEstado::Error->value] ?? 0),
            'advertencias' => (int) ($stats[ImportRowEstado::Advertencia->value] ?? 0),
            'ignoradas' => (int) ($stats[ImportRowEstado::Ignorado->value] ?? 0),
            'clientes_autocreados' => $clienteResolver->totalAutocreados(),
            'estado' => ImportEstado::Completado,
            'finished_at' => now(),
            'resumen' => [
                'por_hoja' => $this->conteoPorHoja,
                'clientes_a_resolver' => $clienteResolver->nombresAutocreados(),
            ],
        ])->save();
    }
}
