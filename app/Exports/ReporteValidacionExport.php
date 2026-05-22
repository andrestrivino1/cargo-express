<?php

namespace App\Exports;

use App\Models\ImportBatch;
use App\Models\ImportRowResult;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class ReporteValidacionExport implements WithMultipleSheets
{
    public function __construct(private readonly ImportBatch $batch)
    {
    }

    public function sheets(): array
    {
        return [
            new ReporteValidacionResumenSheet($this->batch),
            new ReporteValidacionPorHojaSheet($this->batch),
            new ReporteValidacionErroresSheet($this->batch),
        ];
    }
}

class ReporteValidacionResumenSheet implements FromArray, WithHeadings, WithTitle
{
    use Exportable;

    public function __construct(private readonly ImportBatch $batch)
    {
    }

    public function title(): string
    {
        return 'Resumen';
    }

    public function headings(): array
    {
        return ['Campo', 'Valor'];
    }

    public function array(): array
    {
        return [
            ['archivo', $this->batch->archivo_nombre],
            ['hash', $this->batch->archivo_hash],
            ['modo', $this->batch->modo],
            ['estado', $this->batch->estado->value],
            ['total_filas', $this->batch->total_filas],
            ['importables', $this->batch->importables],
            ['errores', $this->batch->errores],
            ['advertencias', $this->batch->advertencias],
            ['ignoradas', $this->batch->ignoradas],
            ['clientes_autocreados', $this->batch->clientes_autocreados],
            ['started_at', $this->batch->started_at?->toIso8601String()],
            ['finished_at', $this->batch->finished_at?->toIso8601String()],
        ];
    }
}

class ReporteValidacionPorHojaSheet implements FromArray, WithHeadings, WithTitle
{
    use Exportable;

    public function __construct(private readonly ImportBatch $batch)
    {
    }

    public function title(): string
    {
        return 'Por hoja';
    }

    public function headings(): array
    {
        return ['Hoja', 'Estado', 'Total'];
    }

    public function array(): array
    {
        return ImportRowResult::query()
            ->selectRaw('hoja, estado, COUNT(*) as total')
            ->where('import_batch_id', $this->batch->id)
            ->groupBy('hoja', 'estado')
            ->orderBy('hoja')
            ->get()
            ->map(fn ($r) => [$r->hoja, (string) $r->estado, (int) $r->total])
            ->all();
    }
}

class ReporteValidacionErroresSheet implements FromArray, WithHeadings, WithTitle
{
    use Exportable;

    public function __construct(private readonly ImportBatch $batch)
    {
    }

    public function title(): string
    {
        return 'Errores';
    }

    public function headings(): array
    {
        return ['Hoja', 'Fila Excel', 'Tipo', 'Mensaje'];
    }

    public function array(): array
    {
        return ImportRowResult::query()
            ->where('import_batch_id', $this->batch->id)
            ->where('estado', 'error')
            ->orderBy('hoja')
            ->orderBy('fila_excel')
            ->get()
            ->map(fn ($r) => [$r->hoja, $r->fila_excel, $r->tipo, $r->mensaje])
            ->all();
    }
}
