<?php

namespace App\Http\Controllers;

use App\Enums\ContenedorEstado;
use App\Http\Requests\StoreOrdenVaciadoRequest;
use App\Models\Contenedor;
use App\Models\OrdenVaciado;
use App\Services\VaciadoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class VaciadoController extends Controller
{
    public function __construct(
        private readonly VaciadoService $vaciadoService
    ) {}

    public function index(): View
    {
        $ordenes = OrdenVaciado::with(['contenedor', 'supervisor'])
            ->latest()
            ->paginate(15);

        return view('vaciado.index', compact('ordenes'));
    }

    public function create(): View
    {
        $contenedores = Contenedor::where('estado', ContenedorEstado::EnPatio)->get();

        return view('vaciado.create', compact('contenedores'));
    }

    public function store(StoreOrdenVaciadoRequest $request): RedirectResponse
    {
        $this->vaciadoService->programar(
            $request->validated(),
            $request->user()
        );

        return redirect()->route('vaciado.index')
            ->with('success', 'Orden de vaciado programada exitosamente.');
    }

    public function show(OrdenVaciado $ordenVaciado): View
    {
        $ordenVaciado->load([
            'contenedor.referencias',
            'supervisor',
            'novedades.operador',
            'novedades.referencia',
            'novedades.photos',
        ]);

        $referencias = $ordenVaciado->contenedor->referencias;

        return view('vaciado.show', compact('ordenVaciado', 'referencias'));
    }

    public function iniciar(OrdenVaciado $ordenVaciado): RedirectResponse
    {
        $this->vaciadoService->iniciar($ordenVaciado);

        return redirect()->route('vaciado.show', $ordenVaciado)
            ->with('success', 'Vaciado iniciado exitosamente.');
    }

    public function finalizar(OrdenVaciado $ordenVaciado): RedirectResponse
    {
        $this->vaciadoService->finalizar($ordenVaciado);

        return redirect()->route('vaciado.show', $ordenVaciado)
            ->with('success', 'Vaciado finalizado exitosamente.');
    }

    public function novedadesPdf(OrdenVaciado $ordenVaciado): Response
    {
        $ordenVaciado->load([
            'contenedor.ordenServicio.solicitud.cliente',
            'supervisor',
            'novedades.operador',
            'novedades.referencia',
            'novedades.photos',
        ]);

        $pdf = Pdf::loadView('pdf.reporte-novedades', compact('ordenVaciado'));

        return $pdf->download("reporte-novedades-{$ordenVaciado->id}.pdf");
    }
}