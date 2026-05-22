<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGateInRequest;
use App\Models\GateEvent;
use App\Models\OrdenServicio;
use App\Models\Producto;
use App\Services\GateInService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class GateInController extends Controller
{
    public function __construct(
        private readonly GateInService $gateInService
    ) {}

    public function index(): View
    {
        $pendientes = $this->gateInService->listarPendientes();

        $ultimosIngresos = GateEvent::where('tipo', 'gate_in')
            ->with(['contenedor.ordenServicio.solicitud.cliente', 'usuario'])
            ->latest('hora')
            ->take(20)
            ->get();

        return view('gate-in.index', compact('pendientes', 'ultimosIngresos'));
    }

    public function create(): View
    {
        $productos = Producto::activos()->orderBy('nombre')->get();

        // Solicitudes listas para gate-in:
        // contenedores en estado Solicitado con orden de servicio activa
        $pendientesIngreso = \App\Models\Contenedor::where('estado', \App\Enums\ContenedorEstado::Solicitado)
            ->whereHas('ordenServicio', fn($q) => $q->where('estado', \App\Enums\OrdenServicioEstado::Activa))
            ->with(['ordenServicio.solicitud.cliente'])
            ->get();

        return view('gate-in.create', compact('productos', 'pendientesIngreso'));
    }

    public function store(StoreGateInRequest $request): RedirectResponse
    {
        $this->gateInService->registrarIngreso(
            $request->validated(),
            $request->user()
        );

        return redirect()->route('gate-in.index')
            ->with('success', 'Ingreso registrado exitosamente.');
    }

    public function buscarOrden(int $id): JsonResponse
    {
        $orden = OrdenServicio::with('solicitud.cliente')->find($id);

        if (!$orden) {
            return response()->json(['error' => 'Orden no encontrada'], 404);
        }

        $solicitud = $orden->solicitud;

        return response()->json([
            'orden_id'           => $orden->id,
            'estado'             => $orden->estado->label(),
            'solicitud_id'       => $solicitud?->id,
            'numero_contenedor'  => $solicitud?->numero_contenedor,
            'cliente_nombre'     => $solicitud?->cliente?->name,
            'cliente_email'      => $solicitud?->cliente?->email,
        ]);
    }

    public function resumenPdf(GateEvent $gateEvent): Response
    {
        $gateEvent->load([
            'contenedor.ordenServicio.solicitud.cliente',
            'contenedor.referencias',
            'usuario',
            'photos',
        ]);

        $pdf = Pdf::loadView('pdf.resumen-ingreso', compact('gateEvent'));

        return $pdf->download("resumen-ingreso-{$gateEvent->id}.pdf");
    }
}