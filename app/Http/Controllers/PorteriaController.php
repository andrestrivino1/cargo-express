<?php

namespace App\Http\Controllers;

use App\Enums\PorteriaFotoCategoria;
use App\Http\Requests\StorePorteriaLlegadaRequest;
use App\Http\Requests\StorePorteriaNovedadRequest;
use App\Models\Cita;
use App\Services\PorteriaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PorteriaController extends Controller
{
    public function __construct(
        private readonly PorteriaService $porteria,
    ) {}

    public function index(Request $request): View
    {
        $busqueda = $request->query('q');

        return view('porteria.index', [
            // Las de hoy son accionables; las próximas, solo seguimiento.
            'citas' => $this->porteria->citasDeHoy($busqueda),
            'proximas' => $this->porteria->citasProximas($busqueda),
            'busqueda' => $busqueda,
        ]);
    }

    public function show(Cita $cita): View
    {
        // El portero alcanza las citas de hoy (para confirmarlas) y las futuras
        // (solo para consultarlas). Las de fechas pasadas quedan fuera del módulo.
        abort_if($cita->fecha_esperada?->isBefore(today()) ?? true, 404);

        $cita->load(['ingreso.cliente', 'contenedor', 'registroPorteria.portero', 'registroPorteria.photos']);

        return view('porteria.show', [
            'cita' => $cita,
            'categorias' => PorteriaFotoCategoria::cases(),
        ]);
    }

    public function registrarLlegada(StorePorteriaLlegadaRequest $request, Cita $cita): RedirectResponse
    {
        abort_if($cita->fecha_esperada?->isBefore(today()) ?? true, 404);
        abort_if($cita->estaAtendida(), 403, 'Esta cita ya fue confirmada en portería.');

        $this->porteria->confirmarLlegada(
            $cita,
            $request->evidencias(),
            $request->validated('observaciones'),
            $request->user(),
        );

        return redirect()->route('porteria.show', $cita)
            ->with('success', "Llegada confirmada para el contenedor {$cita->numero_contenedor}.");
    }

    public function crearNovedad(Request $request): View
    {
        return view('porteria.novedad', [
            'placa' => $request->query('placa'),
        ]);
    }

    public function guardarNovedad(StorePorteriaNovedadRequest $request): RedirectResponse
    {
        $this->porteria->registrarNovedad($request->validated(), $request->user());

        return redirect()->route('porteria.index')
            ->with('success', 'Novedad registrada. Queda para revisión posterior; no genera una cita.');
    }
}
