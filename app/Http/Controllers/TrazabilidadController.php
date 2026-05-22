<?php

namespace App\Http\Controllers;

use App\Models\Contenedor;
use App\Services\TrazabilidadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class TrazabilidadController extends Controller
{
    public function __construct(
        private readonly TrazabilidadService $trazabilidadService,
    ) {}

    /**
     * Mostrar formulario de búsqueda de trazabilidad.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $contenedor = null;
        $historial = null;
        $busqueda = $request->input('numero');
        $noEncontrado = false;

        if ($busqueda) {
            $contenedor = $this->trazabilidadService->buscarContenedor($busqueda);

            if ($contenedor) {
                return redirect()->route('trazabilidad.show', $contenedor);
            }

            $noEncontrado = true;
        }

        return view('trazabilidad.index', compact('busqueda', 'noEncontrado'));
    }

    /**
     * Mostrar timeline completo del contenedor.
     */
    public function show(Contenedor $contenedor): View
    {
        // Recargar con todas las relaciones
        $contenedor = $this->trazabilidadService->buscarContenedor($contenedor->numero);
        $historial = $this->trazabilidadService->obtenerHistorial($contenedor);

        return view('trazabilidad.show', compact('contenedor', 'historial'));
    }

    /**
     * Generar PDF del historial del contenedor.
     */
    public function historialPdf(Contenedor $contenedor): Response
    {
        $contenedor = $this->trazabilidadService->buscarContenedor($contenedor->numero);

        return $this->trazabilidadService->exportarHistorialPdf($contenedor);
    }
}
