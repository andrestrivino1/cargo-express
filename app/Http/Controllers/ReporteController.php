<?php

namespace App\Http\Controllers;

use App\Enums\MovimientoTipo;
use App\Models\User;
use App\Services\ReporteService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReporteController extends Controller
{
    public function __construct(
        private readonly ReporteService $reporteService,
    ) {}

    /**
     * Mostrar dashboard de reportes.
     */
    public function index(): View
    {
        return view('reportes.index');
    }

    public function inventarioPorCliente(Request $request): View
    {
        $clientes = User::role('cliente')->orderBy('name')->get();
        $datos = $this->reporteService->inventarioPorCliente($request->only('cliente_id'));

        return view('reportes.inventario-por-cliente', compact('clientes', 'datos'));
    }

    public function ingresos(Request $request): View
    {
        $movimientos = $this->reporteService->movimientos(
            $request->only('cliente_id', 'fecha_desde', 'fecha_hasta'),
            MovimientoTipo::Entrada,
            porFechaReferencia: true,
        );

        return view('reportes.movimientos', [
            'movimientos' => $movimientos,
            'titulo' => 'Ingresos',
            'tipo' => 'ingresos',
            'usarFechaIngreso' => true,
        ]);
    }

    public function salidas(Request $request): View
    {
        $movimientos = $this->reporteService->movimientos(
            $request->only('cliente_id', 'fecha_desde', 'fecha_hasta'),
            MovimientoTipo::Salida,
        );

        return view('reportes.movimientos', [
            'movimientos' => $movimientos,
            'titulo' => 'Salidas',
            'tipo' => 'salidas',
        ]);
    }

    public function movimientos(Request $request): View
    {
        $movimientos = $this->reporteService->movimientos(
            $request->only('cliente_id', 'fecha_desde', 'fecha_hasta'),
        );

        return view('reportes.movimientos', [
            'movimientos' => $movimientos,
            'titulo' => 'Historial de movimientos',
            'tipo' => 'movimientos',
        ]);
    }

    public function novedades(Request $request): View
    {
        $novedades = $this->reporteService->novedades($request->only('fecha_desde', 'fecha_hasta'));

        return view('reportes.novedades', compact('novedades'));
    }

    public function evidencias(Request $request): View
    {
        $evidencias = $this->reporteService->evidencias($request->only('fecha_desde', 'fecha_hasta'));

        return view('reportes.evidencias', compact('evidencias'));
    }

    /**
     * Mostrar reporte de operación con filtros.
     */
    public function operacion(Request $request): View
    {
        $clientes = User::role('cliente')->orderBy('name')->get();

        $filtros = $request->only(['cliente_id', 'fecha_desde', 'fecha_hasta']);
        $datos = null;

        if ($request->anyFilled(['cliente_id', 'fecha_desde', 'fecha_hasta'])) {
            $datos = $this->reporteService->generarReporteOperacion($filtros);
        }

        return view('reportes.operacion', compact('clientes', 'filtros', 'datos'));
    }

    /**
     * Exportar reporte de operación en el formato solicitado.
     */
    public function export(Request $request): BinaryFileResponse|Response
    {
        $request->validate([
            'formato' => 'required|in:excel,pdf',
            'cliente_id' => 'nullable|exists:users,id',
            'fecha_desde' => 'nullable|date',
            'fecha_hasta' => 'nullable|date|after_or_equal:fecha_desde',
        ]);

        $filtros = $request->only(['cliente_id', 'fecha_desde', 'fecha_hasta']);
        $formato = $request->input('formato');

        if ($formato === 'excel') {
            $export = $this->reporteService->exportarExcel($filtros);

            return Excel::download($export, 'reporte-operacion-'.now()->format('Y-m-d').'.xlsx');
        }

        return $this->reporteService->exportarPdf($filtros);
    }
}
