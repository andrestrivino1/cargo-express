<?php

namespace App\Imports;

use App\Imports\Sheets\ClienteSheetImport;
use App\Models\ImportBatch;
use App\Services\Importacion\ExcelHeaderResolver;
use App\Services\Importacion\ImportReportBuilder;
use App\Services\Importacion\InventarioImportService;
use App\Services\Importacion\RowValidator;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Import raíz que el job dispara. Para cada hoja procesable, devuelve un
 * ClienteSheetImport. Ignora hojas vacías y respaldos manuales ("Copia de…").
 */
class InventarioHistoricoImport implements WithMultipleSheets
{
    /** @var string[] */
    public array $hojasIgnoradas = [];

    public function __construct(
        private readonly InventarioImportService $service,
        private readonly ImportBatch $batch,
        private readonly ExcelHeaderResolver $resolver,
        private readonly RowValidator $validator,
        private readonly ImportReportBuilder $reporte,
    ) {}

    /**
     * Maatwebsite acepta un mapa nombre_hoja => Importable. Las hojas no
     * incluidas se omiten silenciosamente (sin lanzar excepción).
     */
    public function sheets(): array
    {
        $mapa = [];

        foreach ($this->service->nombresDeHojas($this->batch) as $nombreHoja) {
            if ($this->esIgnorable($nombreHoja)) {
                $this->reporte->registrarHojaIgnorada(
                    $this->batch,
                    $nombreHoja,
                    $this->tipoIgnorable($nombreHoja),
                    'Hoja excluida por nombre',
                );
                $this->hojasIgnoradas[] = $nombreHoja;

                continue;
            }

            $mapa[$nombreHoja] = new ClienteSheetImport(
                $this->service,
                $this->batch,
                $nombreHoja,
                $this->resolver,
                $this->validator,
                $this->reporte,
            );
        }

        return $mapa;
    }

    private function esIgnorable(string $nombre): bool
    {
        $normalizado = mb_strtolower(trim($nombre));

        return $normalizado === 'hoja1'
            || str_starts_with($normalizado, 'copia de');
    }

    private function tipoIgnorable(string $nombre): string
    {
        return str_starts_with(mb_strtolower(trim($nombre)), 'copia de')
            ? 'HOJA_COPIA'
            : 'HOJA_VACIA';
    }
}
