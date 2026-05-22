<?php

namespace App\Http\Controllers;

use App\Enums\ContenedorEstado;
use App\Enums\SolicitudEstado;
use App\Http\Requests\StoreOrdenServicioRequest;
use App\Models\Contenedor;
use App\Models\OrdenServicio;
use App\Models\Solicitud;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class OrdenServicioController extends Controller
{
    public function store(StoreOrdenServicioRequest $request, Solicitud $solicitud): RedirectResponse
    {
        $orden = OrdenServicio::create([
            'solicitud_id' => $solicitud->id,
            'coordinador_id' => $request->user()->id,
            'vehiculo' => $request->validated('vehiculo'),
            'conductor' => $request->validated('conductor'),
            'conductor_documento' => $request->validated('conductor_documento'),
            'cita_puerto' => $request->validated('cita_puerto'),
        ]);

        Contenedor::create([
            'orden_servicio_id' => $orden->id,
            'numero' => $solicitud->numero_contenedor,
            'estado' => ContenedorEstado::Solicitado->value,
        ]);

        $solicitud->update(['estado' => SolicitudEstado::Asignada]);

        return redirect()->route('solicitudes.show', $solicitud)
            ->with('success', 'Orden de servicio creada exitosamente.');
    }

    public function pdf(Solicitud $solicitud): Response
    {
        $solicitud->load(['cliente', 'ordenServicio.coordinador']);

        $pdf = Pdf::loadView('pdf.orden-servicio', [
            'solicitud' => $solicitud,
            'orden' => $solicitud->ordenServicio,
        ]);

        return $pdf->download("orden-servicio-{$solicitud->id}.pdf");
    }
}