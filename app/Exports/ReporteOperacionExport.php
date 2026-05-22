<?php

namespace App\Exports;

use App\Exports\Sheets\MovimientosSheet;
use App\Exports\Sheets\NovedadesSheet;
use App\Exports\Sheets\ResumenSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ReporteOperacionExport implements WithMultipleSheets
{
    public function __construct(
        private readonly array $filtros,
    ) {}

    /**
     * @return array
     */
    public function sheets(): array
    {
        return [
            new MovimientosSheet($this->filtros),
            new NovedadesSheet($this->filtros),
            new ResumenSheet($this->filtros),
        ];
    }
}
