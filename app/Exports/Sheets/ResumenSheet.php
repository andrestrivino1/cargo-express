<?php

namespace App\Exports\Sheets;

use App\Models\Referencia;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ResumenSheet implements FromArray, WithHeadings, WithTitle, WithStyles
{
    public function __construct(
        private readonly array $filtros,
    ) {}

    public function array(): array
    {
        $query = Referencia::query()
            ->with('cliente')
            ->select('cliente_id')
            ->selectRaw('COUNT(*) as total_referencias')
            ->selectRaw('AVG(DATEDIFF(COALESCE(fecha_salida, NOW()), fecha_ingreso)) as promedio_dias')
            ->selectRaw('SUM(DATEDIFF(COALESCE(fecha_salida, NOW()), fecha_ingreso)) as total_dias')
            ->whereNotNull('fecha_ingreso')
            ->groupBy('cliente_id');

        if (!empty($this->filtros['cliente_id'])) {
            $query->where('cliente_id', $this->filtros['cliente_id']);
        }

        if (!empty($this->filtros['fecha_desde'])) {
            $query->where('fecha_ingreso', '>=', $this->filtros['fecha_desde']);
        }

        if (!empty($this->filtros['fecha_hasta'])) {
            $query->where('fecha_ingreso', '<=', Carbon::parse($this->filtros['fecha_hasta'])->endOfDay());
        }

        return $query->get()->map(function ($item) {
            return [
                $item->cliente?->name ?? 'N/A',
                $item->total_referencias,
                round($item->promedio_dias, 1),
                (int) $item->total_dias,
            ];
        })->toArray();
    }

    public function headings(): array
    {
        return [
            'Cliente',
            'Total Referencias',
            'Promedio Días Almacenamiento',
            'Total Días Almacenamiento',
        ];
    }

    public function title(): string
    {
        return 'Resumen';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
