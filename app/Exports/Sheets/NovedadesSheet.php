<?php

namespace App\Exports\Sheets;

use App\Models\Novedad;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class NovedadesSheet implements FromQuery, WithHeadings, WithMapping, WithTitle, WithStyles
{
    public function __construct(
        private readonly array $filtros,
    ) {}

    public function query(): Builder
    {
        $query = Novedad::query()
            ->with(['ordenVaciado.contenedor.ordenServicio.solicitud.cliente', 'operador', 'referencia']);

        if (!empty($this->filtros['cliente_id'])) {
            $query->whereHas('ordenVaciado.contenedor.ordenServicio.solicitud', function ($q) {
                $q->where('cliente_id', $this->filtros['cliente_id']);
            });
        }

        if (!empty($this->filtros['fecha_desde'])) {
            $query->where('created_at', '>=', $this->filtros['fecha_desde']);
        }

        if (!empty($this->filtros['fecha_hasta'])) {
            $query->where('created_at', '<=', Carbon::parse($this->filtros['fecha_hasta'])->endOfDay());
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Tipo',
            'Contenedor',
            'Cliente',
            'Referencia',
            'Operador',
            'Descripción',
        ];
    }

    /**
     * @param Novedad $novedad
     */
    public function map($novedad): array
    {
        return [
            $novedad->created_at?->format('d/m/Y H:i') ?? 'N/A',
            $novedad->tipo?->label() ?? $novedad->tipo,
            $novedad->ordenVaciado?->contenedor?->numero ?? 'N/A',
            $novedad->ordenVaciado?->contenedor?->ordenServicio?->solicitud?->cliente?->name ?? 'N/A',
            $novedad->referencia?->codigo ?? 'N/A',
            $novedad->operador?->name ?? 'N/A',
            $novedad->descripcion ?? '',
        ];
    }

    public function title(): string
    {
        return 'Novedades';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
