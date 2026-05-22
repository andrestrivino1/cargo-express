<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTarjaRequest;
use App\Models\OrdenCargue;
use App\Models\Tarja;
use App\Services\EntregaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class TarjaController extends Controller
{
    public function __construct(
        private readonly EntregaService $entregaService
    ) {}

    /**
     * Genera una tarja para la orden de cargue.
     */
    public function store(StoreTarjaRequest $request, OrdenCargue $ordenCargue): RedirectResponse
    {
        $tarja = $this->entregaService->generarTarja(
            $ordenCargue,
            $request->validated()['detalles'],
            $request->user()
        );

        return redirect()->route('entregas.tarja.show', $tarja)
            ->with('success', 'Tarja generada exitosamente.');
    }

    /**
     * Muestra el detalle de una tarja.
     */
    public function show(Tarja $tarja)
    {
        $tarja->load(['ordenCargue.cliente', 'despachador', 'detalles.referencia', 'detalles.ubicacionOrigen']);

        return view('tarjas.show', compact('tarja'));
    }

    /**
     * Genera PDF de la tarja.
     */
    public function pdf(Tarja $tarja): Response
    {
        $tarja->load(['ordenCargue.cliente', 'despachador', 'detalles.referencia', 'detalles.ubicacionOrigen']);

        $pdf = Pdf::loadView('pdf.tarja', compact('tarja'))
            ->setPaper('letter');

        return $pdf->download("tarja-{$tarja->id}.pdf");
    }
}
