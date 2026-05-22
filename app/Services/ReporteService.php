<?php

namespace App\Services;

use App\Exports\ReporteOperacionExport;
use App\Models\GateEvent;
use App\Models\Novedad;
use App\Models\Referencia;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ReporteService
{
    /**
     * Generar reporte de operación con filtros.
     */
    public function generarReporteOperacion(array $filtros): array
    {
        $movimientos = $this->obtenerMovimientos($filtros);
        $novedades = $this->obtenerNovedades($filtros);
        $resumen = $this->obtenerResumenAlmacenamiento($filtros);

        return [
            'movimientos' => $movimientos,
            'novedades' => $novedades,
            'resumen' => $resumen,
            'filtros' => $filtros,
        ];
    }

    /**
     * Exportar reporte de operación en Excel.
     */
    public function exportarExcel(array $filtros): ReporteOperacionExport
    {
        return new ReporteOperacionExport($filtros);
    }

    /**
     * Exportar reporte de operación en PDF.
     */
    public function exportarPdf(array $filtros): Response
    {
        $datos = $this->generarReporteOperacion($filtros);

        $pdf = Pdf::loadView('pdf.reporte-operacion', $datos);
        $pdf->setPaper('A4', 'landscape');

        return $pdf->download('reporte-operacion-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Obtener movimientos (gate events) filtrados.
     */
    private function obtenerMovimientos(array $filtros): \Illuminate\Database\Eloquent\Collection
    {
        $query = GateEvent::query()
            ->with(['contenedor.ordenServicio.solicitud.cliente', 'usuario']);

        if (!empty($filtros['cliente_id'])) {
            $query->whereHas('contenedor.ordenServicio.solicitud', function ($q) use ($filtros) {
                $q->where('cliente_id', $filtros['cliente_id']);
            });
        }

        if (!empty($filtros['fecha_desde'])) {
            $query->where('hora', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query->where('hora', '<=', Carbon::parse($filtros['fecha_hasta'])->endOfDay());
        }

        return $query->orderBy('hora', 'desc')->get();
    }

    /**
     * Obtener novedades filtradas.
     */
    private function obtenerNovedades(array $filtros): \Illuminate\Database\Eloquent\Collection
    {
        $query = Novedad::query()
            ->with(['ordenVaciado.contenedor.ordenServicio.solicitud.cliente', 'operador', 'referencia']);

        if (!empty($filtros['cliente_id'])) {
            $query->whereHas('ordenVaciado.contenedor.ordenServicio.solicitud', function ($q) use ($filtros) {
                $q->where('cliente_id', $filtros['cliente_id']);
            });
        }

        if (!empty($filtros['fecha_desde'])) {
            $query->where('created_at', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query->where('created_at', '<=', Carbon::parse($filtros['fecha_hasta'])->endOfDay());
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Obtener resumen de días de almacenamiento por cliente.
     */
    private function obtenerResumenAlmacenamiento(array $filtros): \Illuminate\Support\Collection
    {
        $query = Referencia::query()
            ->with('cliente')
            ->select('cliente_id')
            ->selectRaw('COUNT(*) as total_referencias')
            ->selectRaw('AVG(DATEDIFF(COALESCE(fecha_salida, NOW()), fecha_ingreso)) as promedio_dias')
            ->selectRaw('SUM(DATEDIFF(COALESCE(fecha_salida, NOW()), fecha_ingreso)) as total_dias')
            ->whereNotNull('fecha_ingreso')
            ->groupBy('cliente_id');

        if (!empty($filtros['cliente_id'])) {
            $query->where('cliente_id', $filtros['cliente_id']);
        }

        if (!empty($filtros['fecha_desde'])) {
            $query->where('fecha_ingreso', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query->where(function ($q) use ($filtros) {
                $q->where('fecha_ingreso', '<=', Carbon::parse($filtros['fecha_hasta'])->endOfDay());
            });
        }

        $resultados = $query->get();

        return $resultados->map(function ($item) {
            return [
                'cliente_id' => $item->cliente_id,
                'cliente_nombre' => $item->cliente?->name ?? 'N/A',
                'total_referencias' => $item->total_referencias,
                'promedio_dias' => round($item->promedio_dias, 1),
                'total_dias' => (int) $item->total_dias,
            ];
        });
    }
}
