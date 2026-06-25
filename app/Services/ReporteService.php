<?php

namespace App\Services;

use App\Enums\MovimientoTipo;
use App\Exports\ReporteOperacionExport;
use App\Models\GateEvent;
use App\Models\MovimientoInventario;
use App\Models\Novedad;
use App\Models\Photo;
use App\Models\Referencia;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReporteService
{
    /**
     * Inventario actual por cliente (saldo disponible agregado).
     */
    public function inventarioPorCliente(array $filtros): Collection
    {
        $query = Referencia::query()
            ->with('cliente')
            ->where('cantidad_actual', '>', 0);

        if (! empty($filtros['cliente_id'])) {
            $query->where('cliente_id', $filtros['cliente_id']);
        }

        return $query->get()
            ->groupBy('cliente_id')
            ->map(fn ($refs) => [
                'cliente' => $refs->first()->cliente?->name ?? 'N/A',
                'referencias' => $refs->count(),
                'unidades' => $refs->sum('cantidad_actual'),
            ])
            ->values();
    }

    /**
     * Movimientos del ledger filtrados por tipo (ingresos/salidas) o todos.
     */
    public function movimientos(array $filtros, ?MovimientoTipo $tipo = null): LengthAwarePaginator
    {
        $query = MovimientoInventario::query()
            ->with(['referencia.cliente', 'referencia.contenedor', 'usuario']);

        if ($tipo) {
            $query->where('tipo', $tipo);
        }

        if (! empty($filtros['cliente_id'])) {
            $query->whereHas('referencia', fn ($q) => $q->where('cliente_id', $filtros['cliente_id']));
        }

        if (! empty($filtros['fecha_desde'])) {
            $query->where('created_at', '>=', $filtros['fecha_desde']);
        }

        if (! empty($filtros['fecha_hasta'])) {
            $query->where('created_at', '<=', Carbon::parse($filtros['fecha_hasta'])->endOfDay());
        }

        return $query->orderByDesc('created_at')->paginate(20);
    }

    /**
     * Novedades registradas (vaciado/recepción).
     */
    public function novedades(array $filtros): LengthAwarePaginator
    {
        $query = Novedad::query()
            ->with(['ordenVaciado.contenedor', 'operador', 'referencia']);

        if (! empty($filtros['fecha_desde'])) {
            $query->where('created_at', '>=', $filtros['fecha_desde']);
        }

        if (! empty($filtros['fecha_hasta'])) {
            $query->where('created_at', '<=', Carbon::parse($filtros['fecha_hasta'])->endOfDay());
        }

        return $query->orderByDesc('created_at')->paginate(20);
    }

    /**
     * Evidencias fotográficas registradas en el sistema.
     */
    public function evidencias(array $filtros): LengthAwarePaginator
    {
        $query = Photo::query()
            ->where('tipo', 'foto')
            ->with('photoable');

        if (! empty($filtros['fecha_desde'])) {
            $query->where('created_at', '>=', $filtros['fecha_desde']);
        }

        if (! empty($filtros['fecha_hasta'])) {
            $query->where('created_at', '<=', Carbon::parse($filtros['fecha_hasta'])->endOfDay());
        }

        return $query->orderByDesc('created_at')->paginate(24);
    }

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
