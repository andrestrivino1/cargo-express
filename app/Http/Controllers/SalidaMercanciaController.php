<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSalidaMercanciaRequest;
use App\Http\Requests\UpdateSalidaRequest;
use App\Models\Referencia;
use App\Models\Tarja;
use App\Models\User;
use App\Services\SalidaMercanciaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class SalidaMercanciaController extends Controller
{
    public function __construct(
        private readonly SalidaMercanciaService $salidas,
    ) {}

    public function index(Request $request): View
    {
        $salidas = $this->salidas->listar($request->only('cliente_id'));

        return view('salida.index', compact('salidas'));
    }

    public function create(): View
    {
        $clientes = User::role('cliente')->orderBy('name')->get();

        return view('salida.create', compact('clientes'));
    }

    /**
     * Referencias con saldo disponible para un cliente (AJAX al elegir cliente).
     */
    public function referenciasCliente(User $cliente): JsonResponse
    {
        $referencias = Referencia::query()
            ->where('cliente_id', $cliente->id)
            ->where('cantidad_actual', '>', 0)
            ->with('contenedor')
            ->orderBy('codigo')
            ->get(['id', 'codigo', 'descripcion', 'cantidad_actual', 'unidad_medida', 'contenedor_id']);

        return response()->json($referencias->map(fn (Referencia $ref) => [
            'id' => $ref->id,
            'codigo' => $ref->codigo,
            'descripcion' => $ref->descripcion,
            'cantidad_actual' => $ref->cantidad_actual,
            'unidad_medida' => $ref->unidad_medida,
            'contenedor' => $ref->contenedor?->numero,
        ]));
    }

    public function store(StoreSalidaMercanciaRequest $request): RedirectResponse
    {
        $tarja = $this->salidas->registrar(
            $request->validated(),
            [
                'mercancia' => $request->file('foto_mercancia'),
                'conductor' => $request->file('foto_conductor'),
            ],
            $request->user(),
        );

        return redirect()
            ->route('salida.show', $tarja)
            ->with('success', "Salida registrada. Orden de Salida ODC-{$tarja->consecutivo_odc} generada.");
    }

    public function show(Tarja $tarja): View
    {
        $tarja->load(['ordenCargue.cliente', 'despachador', 'detalles.referencia.contenedor', 'photos']);

        return view('salida.show', compact('tarja'));
    }

    public function edit(Tarja $tarja): View
    {
        $tarja->load('ordenCargue.cliente', 'photos');

        return view('salida.editar', compact('tarja'));
    }

    public function update(UpdateSalidaRequest $request, Tarja $tarja): RedirectResponse
    {
        $data = $request->validated();

        $tarja->update([
            'fecha_entrega' => $data['fecha_salida'],
            'conductor' => $data['conductor'],
            'conductor_cedula' => $data['conductor_cedula'] ?? null,
            'vehiculo' => $data['placa_vehiculo'],
            'transportador' => $data['transportador'],
            'destino' => $data['destino'],
            'observaciones' => $data['observaciones'] ?? null,
        ]);

        $tarja->ordenCargue?->update(['fecha_despacho' => $data['fecha_salida']]);

        if (! empty($data['nit']) && $tarja->ordenCargue) {
            User::whereKey($tarja->ordenCargue->cliente_id)->update(['nit' => $data['nit']]);
        }

        if ($request->hasFile('foto_mercancia')) {
            $this->salidas->reemplazarFoto($tarja, 'foto_mercancia', $request->file('foto_mercancia'));
        }
        if ($request->hasFile('foto_conductor')) {
            $this->salidas->reemplazarFoto($tarja, 'foto_conductor', $request->file('foto_conductor'));
        }

        return redirect()
            ->route('salida.show', $tarja)
            ->with('success', "Salida ODC-{$tarja->consecutivo_odc} actualizada.");
    }

    /**
     * Genera la Orden de Salida (ODC) en PDF con el formato oficial.
     */
    public function ordenSalidaPdf(Tarja $tarja): Response
    {
        $tarja->load(['ordenCargue.cliente', 'detalles.referencia.contenedor', 'photos']);

        $detalles = $tarja->detalles->map(fn ($detalle) => [
            'contenedor' => $detalle->referencia?->contenedor?->numero,
            'descripcion' => $detalle->referencia?->descripcion ?? $detalle->referencia?->codigo,
            'observaciones' => null,
            'cantidad' => $detalle->cantidad_entregada,
        ]);

        $pdf = Pdf::loadView('pdf.orden-salida', [
            'tarja' => $tarja,
            'cliente' => $tarja->ordenCargue?->cliente,
            'detalles' => $detalles,
            'total' => $detalles->sum('cantidad'),
            'empresa' => config('empresa'),
            'fotoMercancia' => $tarja->photos->firstWhere('categoria', 'foto_mercancia'),
            'fotoConductor' => $tarja->photos->firstWhere('categoria', 'foto_conductor'),
        ]);

        return $pdf->stream("ODC-{$tarja->consecutivo_odc}.pdf");
    }
}
