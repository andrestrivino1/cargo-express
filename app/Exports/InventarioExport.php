<?php

namespace App\Exports;

use App\Models\Referencia;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventarioExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private readonly array $filtros,
    ) {}

    public function query(): Builder
    {
        $query = Referencia::query()
            ->with(['contenedor', 'cliente', 'ubicacionPatio']);

        if (!empty($this->filtros['cliente_id'])) {
            $query->where('cliente_id', $this->filtros['cliente_id']);
        }

        if (!empty($this->filtros['codigo'])) {
            $query->where('codigo', 'like', '%' . $this->filtros['codigo'] . '%');
        }

        if (!empty($this->filtros['modulo'])) {
            $query->whereHas('ubicacionPatio', function ($q) {
                $q->where('modulo', $this->filtros['modulo']);
            });
        }

        if (!empty($this->filtros['fecha_desde'])) {
            $query->where('fecha_ingreso', '>=', $this->filtros['fecha_desde']);
        }

        if (!empty($this->filtros['fecha_hasta'])) {
            $query->where('fecha_ingreso', '<=', $this->filtros['fecha_hasta']);
        }

        return $query->orderBy('fecha_ingreso', 'desc');
    }

    public function headings(): array
    {
        return [
            'Referencia',
            'Contenedor',
            'Cliente',
            'Módulo',
            'Posición',
            'Cantidad',
            'Días Almacenamiento',
        ];
    }

    /**
     * @param Referencia $ref
     */
    public function map($ref): array
    {
        $fechaFin = $ref->fecha_salida ?? Carbon::now();
        $diasAlmacenamiento = $ref->fecha_ingreso
            ? (int) $ref->fecha_ingreso->diffInDays($fechaFin)
            : 0;

        return [
            $ref->codigo,
            $ref->contenedor->numero ?? 'N/A',
            $ref->cliente->name ?? 'N/A',
            $ref->ubicacionPatio->modulo ?? 'Sin asignar',
            $ref->ubicacionPatio->posicion ?? 'Sin asignar',
            $ref->cantidad_actual,
            $diasAlmacenamiento,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}