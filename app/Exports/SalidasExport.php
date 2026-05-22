<?php

namespace App\Exports;

use App\Enums\ContenedorEstado;
use App\Enums\GateEventTipo;
use App\Models\Contenedor;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalidasExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private readonly array $filtros,
    ) {}

    public function query(): Builder
    {
        $query = Contenedor::query()
            ->where('estado', ContenedorEstado::FueraDePatio)
            ->with([
                'ordenServicio.solicitud.cliente',
                'gateEvents' => function ($q) {
                    $q->where('tipo', GateEventTipo::GateOut)->with('usuario');
                },
            ]);

        if (!empty($this->filtros['fecha_desde'])) {
            $query->where('fecha_salida', '>=', $this->filtros['fecha_desde']);
        }

        if (!empty($this->filtros['fecha_hasta'])) {
            $query->where('fecha_salida', '<=', $this->filtros['fecha_hasta'] . ' 23:59:59');
        }

        if (!empty($this->filtros['cliente_id'])) {
            $query->whereHas('ordenServicio.solicitud', function ($q) {
                $q->where('cliente_id', $this->filtros['cliente_id']);
            });
        }

        if (!empty($this->filtros['destino'])) {
            $query->where('destino_salida', 'like', '%' . $this->filtros['destino'] . '%');
        }

        return $query->orderBy('fecha_salida', 'desc');
    }

    public function headings(): array
    {
        return [
            'Contenedor',
            'Placa',
            'Cliente',
            'Fecha Ingreso',
            'Fecha Salida',
            'Destino',
            'Limpieza',
            'Portero',
        ];
    }

    /**
     * @param Contenedor $contenedor
     */
    public function map($contenedor): array
    {
        $gateOutEvent = $contenedor->gateEvents
            ->where('tipo', GateEventTipo::GateOut)
            ->first();

        return [
            $contenedor->numero,
            $contenedor->placa_vehiculo ?? 'N/A',
            $contenedor->ordenServicio->solicitud->cliente->name ?? 'N/A',
            $contenedor->fecha_ingreso ? $contenedor->fecha_ingreso->format('d/m/Y H:i') : 'N/A',
            $contenedor->fecha_salida ? $contenedor->fecha_salida->format('d/m/Y H:i') : 'N/A',
            $contenedor->destino_salida ?? 'N/A',
            $contenedor->limpieza_registrada ? 'Sí' : 'No',
            $gateOutEvent?->usuario?->name ?? 'N/A',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}