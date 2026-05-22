<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ReporteService;
use Illuminate\Http\Request;
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
    public function export(Request $request): BinaryFileResponse|\Illuminate\Http\Response
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

            return Excel::download($export, 'reporte-operacion-' . now()->format('Y-m-d') . '.xlsx');
        }

        return $this->reporteService->exportarPdf($filtros);
    }
}
