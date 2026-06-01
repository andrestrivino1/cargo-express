<?php

namespace App\Http\Controllers;

use App\Enums\ContenedorEstado;
use App\Exports\SalidasExport;
use App\Http\Requests\UpdateGateOutRequest;
use App\Models\Contenedor;
use App\Models\GateEvent;
use App\Models\User;
use App\Services\AuditoriaService;
use App\Services\GateOutService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GateOutController extends Controller
{
    public function __construct(
        private readonly GateOutService $gateOutService
    ) {}

    /**
     * Tab 1: Contenedores listos para salida (vaciado_completado)
     * Tab 2: Historial de salidas con filtros
     */
    public function index(Request $request)
    {
        $listosParaSalida = Contenedor::where('estado', ContenedorEstado::VaciadoCompletado)
            ->with(['ordenServicio.solicitud.cliente'])
            ->orderBy('updated_at', 'desc')
            ->paginate(15, ['*'], 'listos_page');

        $filtros = $request->only(['fecha_desde', 'fecha_hasta', 'cliente_id', 'destino']);
        $historialSalidas = $this->gateOutService->listarSalidas($filtros);

        $clientes = User::role('cliente')->orderBy('name')->get();

        return view('gate-out.index', compact(
            'listosParaSalida',
            'historialSalidas',
            'filtros',
            'clientes'
        ));
    }

    /**
     * Muestra detalle pre-salida del contenedor.
     */
    public function show(Contenedor $contenedor)
    {
        $contenedor->load([
            'ordenServicio.solicitud.cliente',
            'gateEvents.usuario',
            'gateEvents.photos',
        ]);

        return view('gate-out.show', compact('contenedor'));
    }

    /**
     * Registra limpieza y destino del contenedor.
     */
    public function registrarLimpieza(Request $request, Contenedor $contenedor): RedirectResponse
    {
        $validated = $request->validate([
            'limpieza' => 'required|boolean',
            'destino' => 'required|string|max:255',
        ]);

        $this->gateOutService->registrarLimpieza(
            $contenedor,
            (bool) $validated['limpieza'],
            $validated['destino']
        );

        return redirect()->route('gate-out.show', $contenedor)
            ->with('success', 'Limpieza y destino registrados exitosamente.');
    }

    /**
     * Registra la salida (Gate Out) del contenedor.
     */
    public function store(Request $request, Contenedor $contenedor): RedirectResponse
    {
        $validated = $request->validate([
            'notas' => 'nullable|string|max:1000',
            'fotos' => 'nullable|array|max:10',
            'fotos.*' => 'image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $this->gateOutService->registrarSalida(
            $contenedor,
            $validated,
            $request->user()
        );

        return redirect()->route('gate-out.index')
            ->with('success', 'Salida (Gate Out) registrada exitosamente.');
    }

    /**
     * Formulario de edición de un evento de salida (Gate Out).
     */
    public function edit(GateEvent $gateEvent): View
    {
        $gateEvent->load('cambiosAuditoria.usuario', 'contenedor');

        return view('gate-out.editar', compact('gateEvent'));
    }

    /**
     * Actualiza un evento de salida (Gate Out) con auditoría.
     */
    public function update(UpdateGateOutRequest $request, GateEvent $gateEvent, AuditoriaService $auditoria): RedirectResponse
    {
        $gateEvent->fill($request->validated());
        $auditoria->registrarCambios($gateEvent, $request->user());
        $gateEvent->save();

        return redirect()->route('gate-out.show', $gateEvent->contenedor)
            ->with('success', 'Salida actualizada correctamente.');
    }

    /**
     * Genera PDF de tirilla de soporte Gate Out.
     */
    public function tirilla(Contenedor $contenedor): Response
    {
        $contenedor->load(['ordenServicio.solicitud.cliente', 'gateEvents' => function ($q) {
            $q->where('tipo', 'gate_out')->with('usuario');
        }]);

        $barcodeGenerator = new BarcodeGeneratorPNG();
        $barcodeImage = base64_encode(
            $barcodeGenerator->getBarcode($contenedor->numero, $barcodeGenerator::TYPE_CODE_128)
        );

        $pdf = Pdf::loadView('pdf.tirilla', compact('contenedor', 'barcodeImage'))
            ->setPaper([0, 0, 226, 600]); // Compact receipt format ~80mm width

        return $pdf->download("tirilla-gate-out-{$contenedor->numero}.pdf");
    }

    /**
     * Exporta historial de salidas a Excel.
     */
    public function exportExcel(Request $request): BinaryFileResponse
    {
        $filtros = $request->only(['fecha_desde', 'fecha_hasta', 'cliente_id', 'destino']);

        return Excel::download(
            new SalidasExport($filtros),
            'historial-salidas-' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}