<?php

namespace App\Exports\Sheets;

use App\Models\GateEvent;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MovimientosSheet implements FromQuery, WithHeadings, WithMapping, WithTitle, WithStyles
{
    public function __construct(
        private readonly array $filtros,
    ) {}

    public function query(): Builder
    {
        $query = GateEvent::query()
            ->with(['contenedor.ordenServicio.solicitud.cliente', 'usuario']);

        if (!empty($this->filtros['cliente_id'])) {
            $query->whereHas('contenedor.ordenServicio.solicitud', function ($q) {
                $q->where('cliente_id', $this->filtros['cliente_id']);
            });
        }

        if (!empty($this->filtros['fecha_desde'])) {
            $query->where('hora', '>=', $this->filtros['fecha_desde']);
        }

        if (!empty($this->filtros['fecha_hasta'])) {
            $query->where('hora', '<=', Carbon::parse($this->filtros['fecha_hasta'])->endOfDay());
        }

        return $query->orderBy('hora', 'desc');
    }

    public function headings(): array
    {
        return [
            'Fecha/Hora',
            'Tipo',
            'Contenedor',
            'Cliente',
            'Usuario',
            'Estado Físico',
            'Notas',
        ];
    }

    /**
     * @param GateEvent $event
     */
    public function map($event): array
    {
        return [
            $event->hora?->format('d/m/Y H:i') ?? 'N/A',
            $event->tipo?->label() ?? $event->tipo,
            $event->contenedor?->numero ?? 'N/A',
            $event->contenedor?->ordenServicio?->solicitud?->cliente?->name ?? 'N/A',
            $event->usuario?->name ?? 'N/A',
            $event->estado_fisico ?? 'N/A',
            $event->notas ?? '',
        ];
    }

    public function title(): string
    {
        return 'Movimientos';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
