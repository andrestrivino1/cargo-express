<?php

namespace App\Jobs;

use App\Enums\ImportEstado;
use App\Models\ImportBatch;
use App\Notifications\ImportacionFinalizada;
use App\Services\Importacion\InventarioImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcesarImportacionInventario implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(public readonly int $importBatchId) {}

    public function handle(InventarioImportService $service): void
    {
        $batch = ImportBatch::query()->findOrFail($this->importBatchId);

        if ($batch->estado !== ImportEstado::Pendiente) {
            return;
        }

        $batch->forceFill([
            'estado' => ImportEstado::Procesando,
            'started_at' => now(),
        ])->save();

        try {
            $service->procesar($batch);
        } catch (Throwable $e) {
            report($e);

            $batch->forceFill([
                'estado' => ImportEstado::Fallido,
                'finished_at' => now(),
                'error_mensaje' => $e->getMessage(),
            ])->save();

            try {
                $batch->usuario?->notify(new ImportacionFinalizada($batch));
            } catch (\Throwable) {
                // notificación es accesoria; ya quedó error_mensaje en el batch
            }

            throw $e;
        }
    }
}
