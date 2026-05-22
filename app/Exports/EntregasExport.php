<?php

namespace App\Exports;

use App\Models\OrdenCargue;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EntregasExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private readonly array $filtros,
    ) {}

    public function query(): Builder
    {
        $query = OrdenCargue::query()
            ->with(['cliente', 'despachador', 'tarjas.detalles']);

        if (!empty($this->filtros['fecha_desde'])) {
            $query->where('fecha_despacho', '>=', $this->filtros['fecha_desde']);
        }

        if (!empty($this->filtros['fecha_hasta'])) {
            $query->where('fecha_despacho', '<=', $this->filtros['fecha_hasta']);
        }

        if (!empty($this->filtros['cliente_id'])) {
            $query->where('cliente_id', $this->filtros['cliente_id']);
        }

        if (!empty($this->filtros['estado'])) {
            $query->where('estado', $this->filtros['estado']);
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'Número Orden',
            'Cliente',
            'Despachador',
            'Fecha Despacho',
            'Estado',
            'Cantidades Totales',
        ];
    }

    /**
     * @param OrdenCargue $orden
     */
    public function map($orden): array
    {
        $cantidadTotal = $orden->tarjas->sum(function ($tarja) {
            return $tarja->detalles->sum('cantidad_entregada');
        });

        return [
            $orden->id,
            $orden->cliente->name ?? 'N/A',
            $orden->despachador->name ?? 'Sin asignar',
            $orden->fecha_despacho->format('d/m/Y'),
            $orden->estado->label(),
            $cantidadTotal,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
