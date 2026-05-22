<?php

namespace App\Http\Controllers;

use App\Enums\ImportEstado;
use App\Exports\FilasErroneasExport;
use App\Exports\ReporteValidacionExport;
use App\Http\Requests\Importacion\SubirImportacionRequest;
use App\Jobs\ProcesarImportacionInventario;
use App\Models\ImportBatch;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImportacionInventarioController extends Controller
{
    public function index(Request $request): View
    {
        $batches = ImportBatch::query()
            ->with('usuario')
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->string('estado')))
            ->when($request->filled('modo'), fn ($q) => $q->where('modo', $request->string('modo')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('importacion.index', compact('batches'));
    }

    public function store(SubirImportacionRequest $request): RedirectResponse
    {
        $disco = config('importacion.disco');
        $archivo = $request->file('archivo');
        $nombreUnico = Str::uuid()->toString().'.xlsx';
        $rutaRelativa = $archivo->storeAs('', $nombreUnico, $disco);

        $batch = ImportBatch::create([
            'usuario_id' => $request->user()->getKey(),
            'archivo_nombre' => $archivo->getClientOriginalName(),
            'archivo_hash' => $request->archivoHash(),
            'archivo_path' => $rutaRelativa,
            'modo' => $request->string('modo')->value(),
            'dry_run' => $request->string('modo')->value() === 'validar',
            'politica_duplicados' => $request->string('politica_duplicados', 'omitir')->value(),
            'fecha_corte' => $request->date('fecha_corte') ?? config('importacion.fecha_corte_default'),
            'origen' => config('importacion.origen_default'),
            'estado' => ImportEstado::Pendiente,
        ]);

        ProcesarImportacionInventario::dispatch($batch->id);

        return redirect()->route('importaciones.show', $batch)
            ->with('status', 'Importación encolada. Te avisaremos cuando termine.');
    }

    public function show(Request $request, ImportBatch $importacione): View|JsonResponse
    {
        $batch = $importacione->load('usuario');

        if ($request->wantsJson()) {
            return response()->json([
                'id' => $batch->id,
                'estado' => $batch->estado->value,
                'modo' => $batch->modo,
                'dry_run' => $batch->dry_run,
                'total_filas' => $batch->total_filas,
                'importables' => $batch->importables,
                'errores' => $batch->errores,
                'advertencias' => $batch->advertencias,
                'ignoradas' => $batch->ignoradas,
                'started_at' => $batch->started_at?->toIso8601String(),
                'finished_at' => $batch->finished_at?->toIso8601String(),
            ]);
        }

        $hojas = $batch->rowResults()
            ->selectRaw('hoja, estado, COUNT(*) as total')
            ->groupBy('hoja', 'estado')
            ->orderBy('hoja')
            ->get()
            ->groupBy('hoja');

        $errores = $batch->rowResults()
            ->where('estado', 'error')
            ->latest('id')
            ->limit(50)
            ->get();

        $clientes = $batch->resumen['clientes_a_resolver'] ?? [];

        return view('importacion.reporte', compact('batch', 'hojas', 'errores', 'clientes'));
    }

    public function cancelar(ImportBatch $importacione): RedirectResponse
    {
        if ($importacione->estado !== ImportEstado::Pendiente) {
            return back()->with('error', 'Solo se pueden cancelar batches pendientes.');
        }

        $importacione->forceFill(['estado' => ImportEstado::Cancelado])->save();

        return back()->with('status', 'Importación cancelada.');
    }

    public function descargarReporteExcel(ImportBatch $importacione): BinaryFileResponse
    {
        $nombre = 'reporte-batch-'.$importacione->id.'.xlsx';

        return Excel::download(new ReporteValidacionExport($importacione), $nombre);
    }

    public function descargarReportePdf(ImportBatch $importacione)
    {
        $batch = $importacione->load('usuario');
        $hojas = $batch->rowResults()
            ->selectRaw('hoja, estado, COUNT(*) as total')
            ->groupBy('hoja', 'estado')
            ->get()
            ->groupBy('hoja');
        $clientes = $batch->resumen['clientes_a_resolver'] ?? [];

        return Pdf::loadView('importacion._reporte_pdf', compact('batch', 'hojas', 'clientes'))
            ->download('reporte-batch-'.$importacione->id.'.pdf');
    }

    public function descargarErroresExcel(ImportBatch $importacione): BinaryFileResponse
    {
        $nombre = 'errores-batch-'.$importacione->id.'.xlsx';

        return Excel::download(new FilasErroneasExport($importacione), $nombre);
    }
}
