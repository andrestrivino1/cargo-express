<?php

namespace App\Exports;

use App\Models\ImportBatch;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Exporta solo las filas en error replicando el payload original
 * y añadiendo una columna `_motivo` para que el usuario corrija y reimporte.
 */
class FilasErroneasExport implements FromArray, WithHeadings, WithTitle
{
    use Exportable;

    public function __construct(private readonly ImportBatch $batch)
    {
    }

    public function title(): string
    {
        return 'Filas con error';
    }

    public function headings(): array
    {
        // Header genérico — el usuario corrige la celda problemática y reimporta.
        return ['Hoja', 'Fila Excel', '_payload', '_motivo'];
    }

    public function array(): array
    {
        return $this->batch->rowResults()
            ->where('estado', 'error')
            ->orderBy('hoja')
            ->orderBy('fila_excel')
            ->get()
            ->map(fn ($r) => [
                $r->hoja,
                $r->fila_excel,
                $r->payload_original !== null ? json_encode($r->payload_original, JSON_UNESCAPED_UNICODE) : '',
                $r->mensaje,
            ])
            ->all();
    }
}
